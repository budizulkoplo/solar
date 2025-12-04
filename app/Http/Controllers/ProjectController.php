<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\NotaPayment;
use App\Models\Cashflow;
use App\Models\KodeTransaksi;
use App\Models\Rekening;
use App\Models\Vendor;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    // Halaman transaksi masuk (in)
    public function in()
    {
        return view('transaksi.project.in');
    }

    // Halaman transaksi keluar (out)
    public function out()
    {
        return view('transaksi.project.out');
    }

    // Datatable untuk transaksi
    public function getdata($type)
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor'
            ])
            ->where('cashflow', $type)
            ->where('idproject', session('active_project_id'));

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
            ->addColumn('project_name', function($row) {
                return $row->project ? $row->project->namaproject : '-';
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
            ->filter(function($query) use ($type) {
                $search = request('search.value');
                
                // JIKA ADA SEARCH, CARI DI SEMUA DATA
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('nota_no', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function($q) use ($search) {
                            $q->where('namavendor', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function($q) use ($search) {
                            $q->where('namaproject', 'like', "%{$search}%");
                        });
                    });
                } 
                // JIKA TIDAK ADA SEARCH, BATASI 1000 DATA TERBARU
                else {
                    $query->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->limit(1000);
                }
            })
            ->order(function($query) {
                // Default order by tanggal desc
                $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            })
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Simpan transaksi
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

            // Ambil project berdasarkan session
            $projectId = session('active_project_id');
            $project = Project::find($projectId);

            if (!$project) {
                throw new \Exception("Project dengan ID {$projectId} tidak ditemukan");
            }

            // Dapatkan idcompany
            $idcompany = $project->idcompany ?? session('active_project_company_id');
            
            if (empty($idcompany)) {
                throw new \Exception("Project '{$project->namaproject}' tidak memiliki company yang terkait");
            }

            // Dapatkan idretail dari project (bisa NULL)
            $idretail = $project->idretail;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            // Hitung total transaksi
            $total = 0;
            foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['nominal'] * $transaction['jml'];
                $total += $itemTotal;
            }

            // Data untuk nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => $project->id,
                'idcompany' => $idcompany,
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
                'message' => 'Transaksi berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transaction Error:', [
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
            // 1. Update saldo rekening
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

            // 2. Buat nota payment
            $notaPayment = NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            // 3. Catat di cashflows
            $cashflow = Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran nota {$nota->nota_no} - {$nota->cashflow}"
            ]);

            return [
                'notaPayment' => $notaPayment,
                'cashflow' => $cashflow,
                'rekening' => $rekening
            ];

        } catch (\Exception $e) {
            \Log::error('Cash payment processing error:', [
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
            \Log::info('=== START Rollback Cash Payment ===', [
                'nota_id' => $nota->id,
                'nota_no' => $nota->nota_no,
                'cashflow' => $nota->cashflow,
                'total' => $nota->total,
                'payment_method' => $nota->paymen_method,
                'status' => $nota->status,
                'idrek' => $nota->idrek
            ]);

            // Cari payment dan cashflow terkait - QUERY YANG LEBIH AKURAT
            $notaPayment = NotaPayment::where('idnota', $nota->id)->first();
            $cashflow = Cashflow::where('idnota', $nota->id)->first();
            
            \Log::info('Data ditemukan untuk rollback:', [
                'notaPayment_exists' => !is_null($notaPayment),
                'cashflow_exists' => !is_null($cashflow),
                'notaPayment_id' => $notaPayment ? $notaPayment->id : 'NULL',
                'cashflow_id' => $cashflow ? $cashflow->id : 'NULL'
            ]);

            // Rollback saldo rekening MESKIPUN PAYMENT/CASHFLOW TIDAK DITEMUKAN
            // Karena saldo harus dikembalikan apapun yang terjadi
            $rekening = Rekening::find($nota->idrek);
            
            if (!$rekening) {
                \Log::error('Rekening tidak ditemukan untuk rollback', [
                    'idrek' => $nota->idrek
                ]);
                throw new \Exception("Rekening dengan ID {$nota->idrek} tidak ditemukan untuk rollback");
            }

            $saldoSebelum = $rekening->saldo;
            
            // LOGIKA ROLLBACK SALDO - PASTIKAN BENAR
            if ($nota->cashflow == 'out') {
                // Transaksi OUT: uang keluar, rollback = uang kembali (saldo bertambah)
                $rekening->saldo += $nota->total;
                $logPerubahan = "+{$nota->total}";
            } else {
                // Transaksi IN: uang masuk, rollback = uang dikurangi (saldo berkurang)
                $rekening->saldo -= $nota->total;
                $logPerubahan = "-{$nota->total}";
            }
            
            $rekening->save();

            \Log::info('ROLLBACK SALDO REKENING', [
                'rekening_id' => $rekening->idrek,
                'rekening_info' => $rekening->norek . ' - ' . $rekening->namarek,
                'cashflow_type' => $nota->cashflow,
                'total_nota' => $nota->total,
                'saldo_sebelum_rollback' => $saldoSebelum,
                'saldo_setelah_rollback' => $rekening->saldo,
                'perubahan' => $logPerubahan,
                'expected_saldo' => $saldoSebelum + ($nota->cashflow == 'out' ? $nota->total : -$nota->total)
            ]);

            // Hapus payment dan cashflow jika ada
            $deletedPayments = 0;
            $deletedCashflows = 0;

            if ($notaPayment) {
                $deletedPayments = NotaPayment::where('idnota', $nota->id)->delete();
                \Log::info('NotaPayment dihapus', [
                    'deleted_count' => $deletedPayments,
                    'nota_id' => $nota->id
                ]);
            }

            if ($cashflow) {
                $deletedCashflows = Cashflow::where('idnota', $nota->id)->delete();
                \Log::info('Cashflow dihapus', [
                    'deleted_count' => $deletedCashflows,
                    'nota_id' => $nota->id
                ]);
            }

            \Log::info('=== FINISH Rollback Cash Payment - SUCCESS ===', [
                'nota_id' => $nota->id,
                'saldo_berhasil_dikembalikan' => true,
                'payments_dihapus' => $deletedPayments,
                'cashflows_dihapus' => $deletedCashflows
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('=== Rollback cash payment ERROR ===', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id,
                'nota_no' => $nota->nota_no,
                'cashflow' => $nota->cashflow,
                'total' => $nota->total,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Ambil saldo rekening
    public function saldoRekening($id)
    {
        try {
            $rekening = Rekening::find($id);
            if (!$rekening) {
                return response()->json(['saldo' => 0]);
            }

            return response()->json(['saldo' => $rekening->saldo]);
        } catch (\Exception $e) {
            \Log::error('Error getting saldo rekening:', [
                'rekening_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['saldo' => 0]);
        }
    }

    // Show detail nota
    public function show($id)
    {
        try {
            $nota = Nota::with([
                'project',
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
                'message' => 'Nota tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Get data untuk form edit
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
            \Log::error('Error in edit method:', [
                'error' => $e->getMessage(),
                'nota_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Nota tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update transaksi dengan aturan perubahan payment method
     */
    public function update(Request $request, $id, $type)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'tanggal' => 'required|date',
                'vendor_id' => 'required|exists:vendors,id',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            // Cari nota yang akan diupdate - PASTIKAN DATA TERKAIT DILOAD
            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);
            
            \Log::info('Data nota sebelum update:', [
                'nota_id' => $nota->id,
                'old_payment_method' => $nota->paymen_method,
                'old_status' => $nota->status,
                'old_total' => $nota->total,
                'payments_count' => $nota->payments->count(),
                'cashflows_count' => $nota->cashflows->count()
            ]);

            // Simpan data lama untuk pengecekan perubahan
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
                $filename = 'nota_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            // Hitung total transaksi baru
            $newTotal = 0;
            foreach ($request->transactions as $transaction) {
                $newTotal += ($transaction['nominal'] * $transaction['jml']);
            }

            \Log::info('Perubahan yang terdeteksi:', [
                'old_payment_method' => $oldPaymentMethod,
                'new_payment_method' => $request->paymen_method,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'old_rekening' => $oldRekening,
                'new_rekening' => $request->idrek
            ]);

            // ATURAN 2: Jika perubahan dari CASH ke TEMPO, rollback pembayaran
            if ($oldPaymentMethod == 'cash' && $request->paymen_method == 'tempo') {
                \Log::info('Trigger rollback: CASH → TEMPO');
                $rollbackResult = $this->rollbackCashPayment($nota);
                \Log::info('Hasil rollback:', ['success' => $rollbackResult]);
            }

            // Update data nota
            $updateData = [
                'nota_no' => $request->nota_no,
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
                \Log::info('Trigger new payment: TEMPO → CASH');
                $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
            }

            // Jika tetap cash tapi ada perubahan total atau rekening, update pembayaran
            if ($oldPaymentMethod == 'cash' && $request->paymen_method == 'cash') {
                $paymentChanged = ($oldTotal != $newTotal) || ($oldRekening != $request->idrek);
                
                if ($paymentChanged) {
                    \Log::info('Trigger payment update: CASH tetap CASH dengan perubahan');
                    $this->rollbackCashPayment($nota);
                    $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diupdate',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update transaction error:', [
                'error' => $e->getMessage(),
                'nota_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus transaksi dengan rollback lengkap (ATURAN 3)
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);

            \Log::info('START Delete Transaction', [
                'nota_id' => $nota->id,
                'payment_method' => $nota->paymen_method,
                'status' => $nota->status,
                'cashflow' => $nota->cashflow,
                'total' => $nota->total,
                'payments_count' => $nota->payments->count(),
                'cashflows_count' => $nota->cashflows->count()
            ]);

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
                \Log::info('Trigger rollback untuk delete transaction');
                $rollbackResult = $this->rollbackCashPayment($nota);
                \Log::info('Hasil rollback saat delete:', ['success' => $rollbackResult]);
            } else {
                \Log::info('Tidak perlu rollback - payment method: ' . $nota->paymen_method . ', status: ' . $nota->status);
            }

            // Hapus file bukti nota jika ada
            if ($nota->bukti_nota) {
                Storage::disk('public')->delete($nota->bukti_nota);
                \Log::info('Bukti nota dihapus', ['path' => $nota->bukti_nota]);
            }

            // Hapus data terkait
            $transactionsDeleted = NotaTransaction::where('idnota', $nota->id)->delete();
            $paymentsDeleted = NotaPayment::where('idnota', $nota->id)->delete();
            $cashflowsDeleted = Cashflow::where('idnota', $nota->id)->delete();

            \Log::info('Data terkait dihapus:', [
                'transactions' => $transactionsDeleted,
                'payments' => $paymentsDeleted,
                'cashflows' => $cashflowsDeleted
            ]);

            // Hapus nota
            $nota->delete();
            \Log::info('Nota dihapus', ['nota_id' => $id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete transaction error:', [
                'error' => $e->getMessage(),
                'nota_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status nota (paid, partial, cancel)
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
                'message' => 'Status transaksi berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update status error:', [
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