<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\NotaPayment;
use App\Models\Cashflow;
use App\Models\KodeTransaksi;
use App\Models\Rekening;
use App\Models\Vendor;
use App\Models\CompanyUnit; 
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class PTController extends Controller
{
    // Halaman transaksi masuk (in) untuk PT
    public function in()
    {
        return view('transaksi.pt.in');
    }

    // Halaman transaksi keluar (out) untuk PT
    public function out()
    {
        return view('transaksi.pt.out');
    }

    // Datatable untuk transaksi PT
    public function getdata($type)
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return DataTables::collection(collect([]))->toJson();
        }

        $query = Nota::with([
                'companyUnit:id,company_name', // Relasi ke company_units
                'vendor:id,namavendor'
            ])
            ->where('cashflow', $type)
            ->where('idcompany', $companyId)
            ->whereNull('idproject'); // Hanya transaksi PT (tanpa project)

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $deleteBtn = $canDelete ? 
                    '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>' :
                    '<button class="btn btn-sm btn-danger" disabled><i class="bi bi-trash"></i></button>';

                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>
                    '.$deleteBtn.'
                </div>';
            })
            ->addColumn('company_name', function($row) {
                return $row->companyUnit ? $row->companyUnit->company_name : '-';
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 2, ',', '.');
            })
            ->editColumn('status', function($row) {
                $badge = [
                    'open' => 'bg-warning',
                    'paid' => 'bg-success', 
                    'partial' => 'bg-info',
                    'cancel' => 'bg-danger'
                ];
                return '<span class="badge '.$badge[$row->status].'">'.ucfirst($row->status).'</span>';
            })
            ->filter(function($query) use ($type, $companyId) {
                $search = request('search.value');
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('nota_no', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function($q) use ($search) {
                            $q->where('namavendor', 'like', "%{$search}%");
                        })
                        ->orWhereHas('companyUnit', function($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                    });
                } 
                else {
                    $query->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->limit(1000);
                }
                
                // Pastikan hanya data PT dan company yang aktif
                $query->where('idcompany', $companyId)
                    ->whereNull('idproject');
            })
            ->order(function($query) {
                $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            })
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Simpan transaksi PT
    public function store(Request $request, $type)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);
            
            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip; // Pastikan field nip ada di tabel users
            $namauser = $user->name; // Atau $user->nama jika fieldnya nama

            // Ambil company dari session
            $companyId = session('active_company_id');
            $company = CompanyUnit::find($companyId);
            // Ambil project berdasarkan session
            $projectId = session('active_project_id');
            $project = Project::find($projectId);
            $idretail = $project->idretail;
            if (!$company) {
                throw new \Exception("Company/PT dengan ID {$companyId} tidak ditemukan");
            }

            // Pastikan rekening milik company ini
            $rekening = Rekening::where('idrek', $request->idrek)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik PT ini");
            }

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_pt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota_pt', $filename, 'public');
            }

            // Hitung total transaksi
            $total = 0;
            foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['nominal'] * $transaction['jml'];
                $total += $itemTotal;
            }

            // Data untuk nota header - idproject NULL karena transaksi PT
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => null, 
                'idcompany' => $companyId,
                'idretail' => $idretail, 
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => $type,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'total' => $total,
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi
            foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['nominal'] * $transaction['jml'];
                
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['nominal'],
                    'jml' => $transaction['jml'],
                    'total' => $itemTotal,
                ]);
            }

            // ATURAN 1: Jika cash, langsung buat pembayaran dan catat cashflow
            if ($request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $total, $request->tanggal);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi PT berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transaction PT Error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses pembayaran cash lengkap
     */
    private function processCashPayment($nota, $idrek, $jumlah, $tanggal)
    {
        try {
            $rekening = Rekening::find($idrek);
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$idrek} tidak ditemukan");
            }

            $saldoAwal = $rekening->saldo;
            
            if ($nota->cashflow == 'out') {
                $rekening->saldo -= $jumlah;
            } else {
                $rekening->saldo += $jumlah;
            }
            
            $rekening->save();

            // Buat nota payment
            $notaPayment = NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            // Catat di cashflows
            $cashflow = Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran nota PT {$nota->nota_no} - {$nota->cashflow}"
            ]);

            return [
                'notaPayment' => $notaPayment,
                'cashflow' => $cashflow,
                'rekening' => $rekening
            ];

        } catch (\Exception $e) {
            \Log::error('Cash payment processing error PT:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    /**
     * Rollback pembayaran cash
     */
    private function rollbackCashPayment($nota)
    {
        try {
            \Log::info('=== START Rollback Cash Payment PT ===', [
                'nota_id' => $nota->id
            ]);

            $notaPayment = NotaPayment::where('idnota', $nota->id)->first();
            $cashflow = Cashflow::where('idnota', $nota->id)->first();

            // Rollback saldo rekening
            $rekening = Rekening::find($nota->idrek);
            
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$nota->idrek} tidak ditemukan untuk rollback");
            }

            $saldoSebelum = $rekening->saldo;
            
            if ($nota->cashflow == 'out') {
                $rekening->saldo += $nota->total;
            } else {
                $rekening->saldo -= $nota->total;
            }
            
            $rekening->save();

            // Hapus payment dan cashflow jika ada
            if ($notaPayment) {
                NotaPayment::where('idnota', $nota->id)->delete();
            }

            if ($cashflow) {
                Cashflow::where('idnota', $nota->id)->delete();
            }

            \Log::info('=== FINISH Rollback Cash Payment PT - SUCCESS ===');

            return true;

        } catch (\Exception $e) {
            \Log::error('=== Rollback cash payment PT ERROR ===', [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Ambil saldo rekening PT
    public function saldoRekening($id)
    {
        try {
            $companyId = session('active_company_id');
            
            $rekening = Rekening::where('idrek', $id)
                ->where('idcompany', $companyId)
                ->first();
                
            if (!$rekening) {
                return response()->json(['saldo' => 0]);
            }

            return response()->json(['saldo' => $rekening->saldo]);
        } catch (\Exception $e) {
            \Log::error('Error getting saldo rekening PT:', [
                'rekening_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['saldo' => 0]);
        }
    }

    // Show detail nota PT
    public function show($id)
    {
        try {
            $nota = Nota::with([
                'companyUnit', // Relasi ke company_units
                'vendor', 
                'transactions.kodeTransaksi',
                'payments.rekening',
                'cashflows'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $nota
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota PT tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Get data untuk form edit PT
     */
    public function edit($id)
    {
        try {
            $nota = Nota::with([
                'vendor',
                'transactions.kodeTransaksi'
            ])->findOrFail($id);

            $data = [
                'nota' => $nota,
                'transactions' => $nota->transactions
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in edit method PT:', [
                'error' => $e->getMessage(),
                'nota_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Nota PT tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update transaksi PT
     */
    public function update(Request $request, $id, $type)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'tanggal' => 'required|date',

                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);
            
            // Pastikan ini adalah transaksi PT
            if (!is_null($nota->idproject)) {
                throw new \Exception("Transaksi ini adalah transaksi project, bukan transaksi PT");
            }

            // Pastikan company sesuai
            $companyId = session('active_company_id');
            if ($nota->idcompany != $companyId) {
                throw new \Exception("Transaksi ini bukan milik PT yang aktif");
            }

            $oldPaymentMethod = $nota->paymen_method;
            $oldTotal = $nota->total;
            $oldRekening = $nota->idrek;

            // Handle upload bukti nota baru
            $buktiNotaPath = $nota->bukti_nota;
            if ($request->hasFile('bukti_nota')) {
                if ($buktiNotaPath && Storage::disk('public')->exists($buktiNotaPath)) {
                    Storage::disk('public')->delete($buktiNotaPath);
                }
                
                $file = $request->file('bukti_nota');
                $filename = 'nota_pt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota_pt', $filename, 'public');
            }

            // Hitung total transaksi baru
            $newTotal = 0;
            foreach ($request->transactions as $transaction) {
                $newTotal += ($transaction['nominal'] * $transaction['jml']);
            }

            // ATURAN 2: Jika perubahan dari CASH ke TEMPO, rollback pembayaran
            if ($oldPaymentMethod == 'cash' && $request->paymen_method == 'tempo') {
                $this->rollbackCashPayment($nota);
            }

            // Update data nota
            $updateData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'tanggal' => $request->tanggal,
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'total' => $newTotal,
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
            ];

            $nota->update($updateData);

            // Hapus detail transaksi lama
            NotaTransaction::where('idnota', $nota->id)->delete();

            // Simpan detail transaksi baru
            foreach ($request->transactions as $transaction) {
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['nominal'],
                    'jml' => $transaction['jml'],
                    'total' => $transaction['nominal'] * $transaction['jml'],
                ]);
            }

            // ATURAN 1: Jika perubahan dari TEMPO ke CASH, buat pembayaran baru
            if ($oldPaymentMethod == 'tempo' && $request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
            }

            // Jika tetap cash tapi ada perubahan total atau rekening, update pembayaran
            if ($oldPaymentMethod == 'cash' && $request->paymen_method == 'cash') {
                $paymentChanged = ($oldTotal != $newTotal) || ($oldRekening != $request->idrek);
                
                if ($paymentChanged) {
                    $this->rollbackCashPayment($nota);
                    $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi PT berhasil diupdate',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update transaction PT error:', [
                'error' => $e->getMessage(),
                'nota_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus transaksi PT
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);

            // Pastikan ini adalah transaksi PT
            if (!is_null($nota->idproject)) {
                throw new \Exception("Transaksi ini adalah transaksi project, tidak dapat dihapus dari menu PT");
            }

            // Cek role user
            $user = auth()->user();
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus transaksi'
                ], 403);
            }

            // ATURAN 3: Rollback pembayaran jika transaksi cash dan status paid
            if ($nota->paymen_method == 'cash' && $nota->status == 'paid') {
                $this->rollbackCashPayment($nota);
            }

            // Hapus file bukti nota jika ada
            if ($nota->bukti_nota) {
                Storage::disk('public')->delete($nota->bukti_nota);
            }

            // Hapus data terkait
            NotaTransaction::where('idnota', $nota->id)->delete();
            NotaPayment::where('idnota', $nota->id)->delete();
            Cashflow::where('idnota', $nota->id)->delete();

            // Hapus nota
            $nota->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi PT berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete transaction PT error:', [
                'error' => $e->getMessage(),
                'nota_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status nota PT
     */
    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'status' => 'required|in:paid,partial,cancel'
            ]);

            $nota = Nota::with(['payments'])->findOrFail($id);
            $oldStatus = $nota->status;

            // Pastikan ini adalah transaksi PT
            if (!is_null($nota->idproject)) {
                throw new \Exception("Transaksi ini adalah transaksi project");
            }

            // Jika status berubah menjadi paid dan payment method cash, buat pembayaran
            if ($request->status == 'paid' && $nota->paymen_method == 'cash' && $oldStatus != 'paid') {
                $this->processCashPayment($nota, $nota->idrek, $nota->total, $nota->tanggal);
            }

            // Jika status berubah dari paid ke status lain dan payment method cash, rollback
            if ($oldStatus == 'paid' && $request->status != 'paid' && $nota->paymen_method == 'cash') {
                $this->rollbackCashPayment($nota);
            }

            $nota->update(['status' => $request->status]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status transaksi PT berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update status PT error:', [
                'error' => $e->getMessage(),
                'nota_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}