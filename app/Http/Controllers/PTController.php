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
use App\Models\TransUpdateLog;
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
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
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
                        ->orWhere('namatransaksi', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%")
                        ->orWhere('namauser', 'like', "%{$search}%")
                        ->orWhereHas('vendor', function($q) use ($search) {
                            $q->where('namavendor', 'like', "%{$search}%");
                        })
                        ->orWhereHas('companyUnit', function($q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%");
                        });
                    });
                } else {
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
            // Validasi berbeda untuk IN (tanpa vendor) dan OUT (dengan vendor)
            $validationRules = [
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'ppn' => 'nullable|numeric|min:0',
                'diskon' => 'nullable|numeric|min:0',
                'ppn_kode' => 'nullable|string',
                'diskon_kode' => 'nullable|string',
                'subtotal' => 'required|numeric|min:0',
            ];

            // Untuk transaksi OUT (keluar), vendor wajib
            if ($type == 'out') {
                $validationRules['bukti_nota'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:2048';
            } else {
                // Untuk transaksi IN (masuk), vendor tidak wajib dan bukti nota optional
                $validationRules['bukti_nota'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048';
            }

            $request->validate($validationRules);
            
            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;

            // Ambil company dari session
            $companyId = session('active_company_id');
            $company = CompanyUnit::find($companyId);
            
            if (!$company) {
                throw new \Exception("Company/PT dengan ID {$companyId} tidak ditemukan");
            }

            // Untuk PT, idproject selalu null
            $idproject = null;
            $idretail = 0; // Default untuk PT

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

            // Gunakan subtotal dari form
            $subtotal = $request->subtotal ?? 0;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $total = $subtotal + $ppn - $diskon;

            // Data untuk nota header - idproject NULL karena transaksi PT
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => $idproject, 
                'idcompany' => $companyId,
                'idretail' => $idretail, 
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => $type,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'total' => $total,
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi regular
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

            // Simpan PPN sebagai transaksi terpisah jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', $request->ppn_kode ?? '3001')->first();
                if ($kodePpn) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodePpn->id,
                        'description' => 'PPN',
                        'nominal' => $ppn,
                        'jml' => 1,
                        'total' => $ppn,
                    ]);
                }
            }

            // Simpan Diskon sebagai transaksi terpisah jika ada
            if ($diskon > 0) {
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', $request->diskon_kode ?? '5001')->first();
                if ($kodeDiskon) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodeDiskon->id,
                        'description' => 'Diskon',
                        'nominal' => $diskon,
                        'jml' => 1,
                        'total' => $diskon,
                    ]);
                }
            }

            // Buat log untuk transaksi baru
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi dibuat - No: {$nota->nota_no}, Total: Rp " . number_format($total, 0, ',', '.'));

            // Jika cash, langsung buat pembayaran
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
     * Create update log
     */
    private function createUpdateLog($notaId, $notaNo, $logMessage)
    {
        return TransUpdateLog::create([
            'idnota' => $notaId,
            'nota_no' => $notaNo,
            'update_log' => $logMessage
        ]);
    }

    /**
     * Get changes between old and new data for logging
     */
    private function getChangesForLog($oldData, $newData, $notaId)
    {
        $changes = [];
        
        $fields = [
            'nota_no' => 'Nomor Nota',
            'namatransaksi' => 'Nama Transaksi',
            'tanggal' => 'Tanggal',
            'vendor_id' => 'Vendor',
            'idrek' => 'Rekening',
            'paymen_method' => 'Payment Method',
            'tgl_tempo' => 'Tanggal Tempo',
            'subtotal' => 'Subtotal',
            'ppn' => 'PPN',
            'diskon' => 'Diskon',
            'total' => 'Total',
            'status' => 'Status'
        ];
        
        foreach ($fields as $field => $label) {
            if (isset($oldData[$field]) && isset($newData[$field])) {
                if ($oldData[$field] != $newData[$field]) {
                    $oldValue = $oldData[$field];
                    $newValue = $newData[$field];
                    
                    // Format khusus untuk beberapa field
                    if ($field == 'vendor_id') {
                        $oldVendor = Vendor::find($oldValue);
                        $newVendor = Vendor::find($newValue);
                        $oldValue = $oldVendor ? $oldVendor->namavendor : 'Tidak ada';
                        $newValue = $newVendor ? $newVendor->namavendor : 'Tidak ada';
                    } elseif ($field == 'idrek') {
                        $oldRek = Rekening::find($oldValue);
                        $newRek = Rekening::find($newValue);
                        $oldValue = $oldRek ? $oldRek->norek . ' - ' . $oldRek->namarek : 'Tidak ada';
                        $newValue = $newRek ? $newRek->norek . ' - ' . $newRek->namarek : 'Tidak ada';
                    } elseif (in_array($field, ['subtotal', 'ppn', 'diskon', 'total'])) {
                        $oldValue = 'Rp ' . number_format($oldValue, 0, ',', '.');
                        $newValue = 'Rp ' . number_format($newValue, 0, ',', '.');
                    } elseif ($field == 'tanggal' || $field == 'tgl_tempo') {
                        $oldValue = date('d/m/Y', strtotime($oldValue));
                        $newValue = date('d/m/Y', strtotime($newValue));
                    }
                    
                    $changes[] = "{$label} diubah: {$oldValue} → {$newValue}";
                }
            }
        }
        
        if (isset($newData['transactions'])) {
            $changes[] = "Detail transaksi diubah: " . count($newData['transactions']) . " item";
        }
        
        return $changes;
    }

    /**
     * Process cash payment
     */
    private function processCashPayment($nota, $idrek, $jumlah, $tanggal)
    {
        try {
            // Update saldo rekening
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
            NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            // Catat di cashflows
            Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran nota PT {$nota->nota_no} - {$nota->cashflow}"
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Cash payment processing error PT:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    /**
     * Rollback cash payment
     */
    private function rollbackCashPayment($nota)
    {
        try {
            \Log::info('=== START Rollback Cash Payment PT ===', [
                'nota_id' => $nota->id,
                'nota_no' => $nota->nota_no,
                'cashflow' => $nota->cashflow,
                'total' => $nota->total,
                'payment_method' => $nota->paymen_method,
                'status' => $nota->status,
                'idrek' => $nota->idrek
            ]);

            // Cari payment dan cashflow terkait
            $notaPayment = NotaPayment::where('idnota', $nota->id)->first();
            $cashflow = Cashflow::where('idnota', $nota->id)->first();
            
            // Rollback saldo rekening
            $rekening = Rekening::find($nota->idrek);
            
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$nota->idrek} tidak ditemukan untuk rollback");
            }

            $saldoSebelum = $rekening->saldo;
            
            // LOGIKA ROLLBACK SALDO: Kembalikan saldo ke kondisi sebelum transaksi
            if ($nota->cashflow == 'out') {
                // Jika transaksi out, tambahkan kembali ke saldo
                $rekening->saldo += $nota->total;
            } else {
                // Jika transaksi in, kurangi dari saldo
                $rekening->saldo -= $nota->total;
            }
            
            $rekening->save();

            \Log::info('ROLLBACK SALDO REKENING PT', [
                'rekening_id' => $rekening->idrek,
                'rekening_info' => $rekening->norek . ' - ' . $rekening->namarek,
                'cashflow_type' => $nota->cashflow,
                'total_nota' => $nota->total,
                'saldo_sebelum_rollback' => $saldoSebelum,
                'saldo_setelah_rollback' => $rekening->saldo
            ]);

            // Hapus payment dan cashflow jika ada
            if ($notaPayment) {
                NotaPayment::where('idnota', $nota->id)->delete();
            }

            if ($cashflow) {
                Cashflow::where('idnota', $nota->id)->delete();
            }

            \Log::info('=== FINISH Rollback Cash Payment PT - SUCCESS ===', [
                'nota_id' => $nota->id,
                'saldo_berhasil_dikembalikan' => true
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('=== Rollback cash payment PT ERROR ===', [
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
                'companyUnit',
                'vendor', 
                'rekening',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                },
                'payments.rekening',
                'cashflows',
                'updateLogs' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
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
     * Get update logs for a nota PT
     */
    public function getUpdateLogs($id)
    {
        try {
            $logs = TransUpdateLog::where('idnota', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil log PT'
            ], 500);
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
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                }
            ])->findOrFail($id);

            // Filter hanya transaksi regular (bukan PPN/diskon)
            $regularTransactions = $nota->transactions->filter(function($transaction) {
                if ($transaction->kodeTransaksi) {
                    return !in_array($transaction->kodeTransaksi->kodetransaksi, ['3001', '5001']);
                }
                return true;
            })->values();

            $data = [
                'nota' => $nota,
                'transactions' => $regularTransactions
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
     * Update transaksi PT dengan logging dan rollback yang benar
     */
    public function update(Request $request, $id, $type)
    {
        DB::beginTransaction();
        try {
            // Validasi berbeda untuk IN dan OUT
            $validationRules = [
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'ppn' => 'nullable|numeric|min:0',
                'diskon' => 'nullable|numeric|min:0',
                'ppn_kode' => 'nullable|string',
                'diskon_kode' => 'nullable|string',
                'old_rekening' => 'nullable|exists:rekening,idrek',
                'old_grand_total' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
            ];

            $request->validate($validationRules);

            // Cari nota yang akan diupdate
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

            // Simpan data lama untuk logging dan rollback
            $oldData = $nota->toArray();
            $oldPaymentMethod = $nota->paymen_method;
            $oldTotal = $nota->total;
            $oldRekening = $request->old_rekening ?? $nota->idrek;
            $oldGrandTotal = $request->old_grand_total ?? $nota->total;

            \Log::info('=== UPDATE TRANSACTION PT START ===', [
                'nota_id' => $id,
                'old_total' => $oldTotal,
                'old_rekening' => $oldRekening,
                'old_payment_method' => $oldPaymentMethod,
                'old_status' => $nota->status,
                'new_rekening_request' => $request->idrek
            ]);

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

            // Gunakan subtotal dari form
            $subtotal = $request->subtotal ?? 0;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $newTotal = $subtotal + $ppn - $diskon;

            \Log::info('New calculation PT:', [
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'new_total' => $newTotal
            ]);

            // 1. ROLLBACK LOGIC - hanya jika transaksi lama adalah CASH dan PAID
            $paymentRollbackNeeded = false;
            $rekeningChanged = ($oldRekening != $request->idrek);
            $totalChanged = ($oldTotal != $newTotal);
            
            if ($oldPaymentMethod == 'cash' && $nota->status == 'paid') {
                \Log::info('Rollback old cash payment PT', [
                    'old_total' => $oldTotal,
                    'old_rekening' => $oldRekening,
                    'rekening_changed' => $rekeningChanged,
                    'total_changed' => $totalChanged
                ]);
                
                // Rollback pembayaran lama - ini akan mengembalikan saldo ke rekening lama
                $this->rollbackCashPayment($nota);
                $paymentRollbackNeeded = true;
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
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'total' => $newTotal,
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
            ];

            $nota->update($updateData);

            // Hapus semua transaksi lama
            NotaTransaction::where('idnota', $nota->id)->delete();

            // Simpan detail transaksi regular
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

            // Simpan PPN jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', $request->ppn_kode ?? '3001')->first();
                if ($kodePpn) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodePpn->id,
                        'description' => 'PPN',
                        'nominal' => $ppn,
                        'jml' => 1,
                        'total' => $ppn,
                    ]);
                }
            }

            // Simpan Diskon jika ada
            if ($diskon > 0) {
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', $request->diskon_kode ?? '5001')->first();
                if ($kodeDiskon) {
                    NotaTransaction::create([
                        'idnota' => $nota->id,
                        'idkodetransaksi' => $kodeDiskon->id,
                        'description' => 'Diskon',
                        'nominal' => $diskon,
                        'jml' => 1,
                        'total' => $diskon,
                    ]);
                }
            }

            // Log perubahan
            $newData = array_merge($updateData, [
                'transactions' => $request->transactions
            ]);
            
            $changes = $this->getChangesForLog($oldData, $newData, $nota->id);
            
            if (!empty($changes)) {
                $logMessage = "Transaksi PT diupdate: " . implode(", ", $changes);
                $this->createUpdateLog($nota->id, $nota->nota_no, $logMessage);
            }

            // 2. PROSES PEMBAYARAN BARU jika transaksi baru adalah CASH
            if ($request->paymen_method == 'cash') {
                \Log::info('Process new cash payment PT', [
                    'new_total' => $newTotal,
                    'new_rekening' => $request->idrek,
                    'previous_rollback' => $paymentRollbackNeeded
                ]);
                
                // Proses pembayaran baru - ini akan memotong rekening baru
                $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
            }

            DB::commit();

            \Log::info('=== UPDATE TRANSACTION PT SUCCESS ===', [
                'nota_id' => $id,
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'rekening_changed' => $rekeningChanged,
                'old_rekening' => $oldRekening,
                'new_rekening' => $request->idrek
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi PT berhasil diupdate',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update transaction PT error:', [
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
     * Hapus transaksi PT dengan rollback dan logging
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

            // Rollback pembayaran jika transaksi cash dan status paid
            if ($nota->paymen_method == 'cash' && $nota->status == 'paid') {
                $this->rollbackCashPayment($nota);
            }

            // Buat log untuk penghapusan
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi PT dihapus - No: {$nota->nota_no}, Total: Rp " . number_format($nota->total, 0, ',', '.'));

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
     * Update status nota PT dengan logging
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

            // Buat log untuk perubahan status
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Status PT diubah: " . ucfirst($oldStatus) . " → " . ucfirst($request->status));

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