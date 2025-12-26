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
use App\Models\PekerjaanKonstruksi;
use App\Models\TransUpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class ConstructionTransactionController extends Controller
{
    /**
     * Halaman index untuk memilih pekerjaan konstruksi
     */
    public function index()
    {
        $companyId = session('active_company_id');
        $projectId = session('active_project_id');

        // Filter pekerjaan berdasarkan session
        $query = PekerjaanKonstruksi::with(['project'])
            ->where('status', '!=', 'canceled');

        if ($projectId) {
            $query->where('idproject', $projectId);
        } elseif ($companyId) {
            $query->whereHas('project', function($q) use ($companyId) {
                $q->where('idcompany', $companyId);
            });
        }

        $pekerjaan = $query->orderBy('created_at', 'desc')->get();

        $statuses = [
            'planning' => 'Planning',
            'ongoing' => 'Sedang Berjalan',
            'completed' => 'Selesai'
        ];

        $jenisPekerjaan = [
            'irigasi' => 'Pembuatan Irigasi',
            'renovasi' => 'Renovasi Bangunan',
            'jalan' => 'Pembuatan Jalan',
            'bangunan' => 'Bangunan Baru',
            'jembatan' => 'Pembangunan Jembatan',
            'drainase' => 'Sistem Drainase',
            'lainnya' => 'Lainnya'
        ];

        return view('construction.transactions.index', compact('pekerjaan', 'statuses', 'jenisPekerjaan'));
    }

    /**
     * Halaman detail transaksi untuk pekerjaan tertentu
     */
    public function showTransactions($pekerjaanId)
    {
        $pekerjaan = PekerjaanKonstruksi::with(['project'])->findOrFail($pekerjaanId);
        
        // Cek apakah pekerjaan milik project yang aktif
        if (session('active_project_id') && $pekerjaan->idproject != session('active_project_id')) {
            abort(403, 'Akses ditolak. Pekerjaan tidak termasuk dalam project aktif.');
        }

        // Hitung total transaksi untuk pekerjaan ini
        $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
            ->where('cashflow', 'out')
            ->sum('total');

        // Hitung realisasi anggaran
        $realisasiAnggaran = $pekerjaan->realisasi_anggaran ?? 0;
        $sisaAnggaran = $pekerjaan->anggaran - $realisasiAnggaran;

        return view('construction.transactions.detail', compact('pekerjaan', 'totalTransaksi', 'realisasiAnggaran', 'sisaAnggaran'));
    }

    /**
     * Datatable untuk transaksi pekerjaan konstruksi
     */
    public function getTransactionsData($pekerjaanId)
    {
        try {
            $query = Nota::with([
                    'project:id,namaproject',
                    'vendor:id,namavendor',
                    'rekening:idrek,norek,namarek'
                ])
                ->where('pekerjaan_konstruksi_id', $pekerjaanId)
                ->where('type', 'konstruksi')
                ->where('cashflow', 'out');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $user = auth()->user();
                    $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                    
                    $deleteBtn = $canDelete ? 
                        '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'">
                            <i class="bi bi-trash"></i>
                        </button>' :
                        '<button class="btn btn-sm btn-danger" disabled>
                            <i class="bi bi-trash"></i>
                        </button>';

                    return '<div class="btn-group">
                        <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'">
                            <i class="bi bi-pencil"></i>
                        </button>
                        '.$deleteBtn.'
                    </div>';
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
                ->editColumn('paymen_method', function($row) {
                    return $row->paymen_method == 'cash' ? 'Cash' : 'Tempo';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error('Error in getTransactionsData: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    /**
     * Form create transaksi untuk pekerjaan konstruksi
     */
    public function create($pekerjaanId)
    {
        $pekerjaan = PekerjaanKonstruksi::with(['project'])->findOrFail($pekerjaanId);
        
        // Validasi project aktif
        if (session('active_project_id') && $pekerjaan->idproject != session('active_project_id')) {
            abort(403, 'Akses ditolak');
        }

        // Hitung sisa anggaran
        $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
            ->where('cashflow', 'out')
            ->where('status', '!=', 'cancel')
            ->sum('total');

        $realisasiAnggaran = $pekerjaan->realisasi_anggaran ?? 0;
        $sisaAnggaran = $pekerjaan->anggaran - max($totalTransaksi, $realisasiAnggaran);

        // Get kode transaksi khusus konstruksi
        $kodeTransaksi = KodeTransaksi::where('type', 'construction')
            ->orWhereNull('type')
            ->orderBy('kodetransaksi')
            ->get();

        $vendors = Vendor::whereNull('deleted_at')->get();
        $rekenings = Rekening::forProject(session('active_project_id'))->get();

        return view('construction.transactions.create', compact(
            'pekerjaan', 
            'kodeTransaksi', 
            'vendors', 
            'rekenings',
            'sisaAnggaran'
        ));
    }

    /**
     * Store transaksi untuk pekerjaan konstruksi
     */
    public function store(Request $request, $pekerjaanId)
    {
        DB::beginTransaction();
        try {
            $pekerjaan = PekerjaanKonstruksi::findOrFail($pekerjaanId);
            
            // Validasi project aktif
            if (session('active_project_id') && $pekerjaan->idproject != session('active_project_id')) {
                throw new \Exception('Akses ditolak');
            }

            $request->validate([
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
                'subtotal' => 'required|numeric|min:0',
            ]);
            $projectId = session('active_project_id');
            $project = Project::find($projectId);
            // Cek sisa anggaran
            $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
                ->where('cashflow', 'out')
                ->where('status', '!=', 'cancel')
                ->sum('total');
            $idretail = $project->idretail;
            $realisasiAnggaran = $pekerjaan->realisasi_anggaran ?? 0;
            $anggaranDigunakan = max($totalTransaksi, $realisasiAnggaran);
            $sisaAnggaran = $pekerjaan->anggaran - $anggaranDigunakan;

            $subtotal = $request->subtotal ?? 0;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $total = $subtotal + $ppn - $diskon;

            if ($total > $sisaAnggaran) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total transaksi melebihi sisa anggaran. Sisa anggaran: Rp ' . number_format($sisaAnggaran, 0, ',', '.')
                ], 400);
            }

            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'konstruksi_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota/konstruksi', $filename, 'public');
            }

            // Data untuk nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => $pekerjaan->idproject,
                'idcompany' => session('active_company_id'),
                'idretail' => $idretail,
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => 'out',
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => $ppn,
                'diskon' => $diskon,
                'total' => $total,
                'type' => 'konstruksi',
                'pekerjaan_konstruksi_id' => $pekerjaanId,
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

            // Simpan PPN jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', '3001')->first();
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
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', '5001')->first();
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

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi konstruksi dibuat untuk pekerjaan: {$pekerjaan->nama_pekerjaan}. Total: Rp " . number_format($total, 0, ',', '.'));

            // Update realisasi anggaran pekerjaan
            $this->updateRealisasiAnggaran($pekerjaanId);

            // Jika cash, langsung buat pembayaran
            if ($request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $total, $request->tanggal);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi konstruksi berhasil disimpan',
                'nota_id' => $nota->id,
                'redirect_url' => route('construction.transactions.detail', $pekerjaanId)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Construction Transaction Error:', [
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
     * Form edit transaksi konstruksi
     */
    public function edit($pekerjaanId, $notaId)
    {
        $pekerjaan = PekerjaanKonstruksi::findOrFail($pekerjaanId);
        $nota = Nota::with([
            'vendor',
            'transactions' => function($q) {
                $q->with('kodeTransaksi')
                  ->orderBy('id');
            }
        ])->where('pekerjaan_konstruksi_id', $pekerjaanId)
          ->findOrFail($notaId);

        // Filter hanya transaksi regular (bukan PPN/diskon)
        $regularTransactions = $nota->transactions->filter(function($transaction) {
            if ($transaction->kodeTransaksi) {
                return !in_array($transaction->kodeTransaksi->kodetransaksi, ['3001', '5001']);
            }
            return true;
        })->values();

        $transactions = $nota->transactions->filter(function($transaction) {
            if ($transaction->kodeTransaksi) {
                return !in_array($transaction->kodeTransaksi->kodetransaksi, ['3001', '5001']);
            }
            return true;
        })->values();

        // Get kode transaksi
        $kodeTransaksi = KodeTransaksi::where('type', 'construction')
            ->orWhereNull('type')
            ->orderBy('kodetransaksi')
            ->get();

        $vendors = Vendor::whereNull('deleted_at')->get();
        $rekenings = Rekening::forProject(session('active_project_id'))->get();

        // Hitung sisa anggaran (exclude nota yang sedang diedit)
        $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
            ->where('cashflow', 'out')
            ->where('status', '!=', 'cancel')
            ->where('id', '!=', $notaId)
            ->sum('total');

        $realisasiAnggaran = $pekerjaan->realisasi_anggaran ?? 0;
        $anggaranDigunakan = max($totalTransaksi, $realisasiAnggaran);
        $sisaAnggaran = $pekerjaan->anggaran - $anggaranDigunakan;

        return view('construction.transactions.edit', compact(
            'pekerjaan',
            'nota',
            'regularTransactions',
            'transactions',
            'kodeTransaksi',
            'vendors',
            'rekenings',
            'sisaAnggaran'
        ));
    }

    /**
     * Update transaksi konstruksi
     */
    public function update(Request $request, $pekerjaanId, $notaId)
    {
        DB::beginTransaction();
        try {
            $pekerjaan = PekerjaanKonstruksi::findOrFail($pekerjaanId);
            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($notaId);
            
            // Validasi project aktif
            if (session('active_project_id') && $pekerjaan->idproject != session('active_project_id')) {
                throw new \Exception('Akses ditolak');
            }

            $request->validate([
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
                'subtotal' => 'required|numeric|min:0',
                'old_rekening' => 'nullable|exists:rekening,idrek',
                'old_grand_total' => 'nullable|numeric|min:0',
            ]);

            // Cek sisa anggaran (exclude nota yang sedang diedit)
            $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
                ->where('cashflow', 'out')
                ->where('status', '!=', 'cancel')
                ->where('id', '!=', $notaId)
                ->sum('total');

            $realisasiAnggaran = $pekerjaan->realisasi_anggaran ?? 0;
            $anggaranDigunakan = max($totalTransaksi, $realisasiAnggaran);
            $sisaAnggaran = $pekerjaan->anggaran - $anggaranDigunakan;

            $subtotal = $request->subtotal ?? 0;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $newTotal = $subtotal + $ppn - $diskon;

            if ($newTotal > $sisaAnggaran + $nota->total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total transaksi melebihi sisa anggaran. Sisa anggaran: Rp ' . number_format($sisaAnggaran, 0, ',', '.')
                ], 400);
            }

            // Simpan data lama untuk rollback
            $oldData = $nota->toArray();
            $oldPaymentMethod = $nota->paymen_method;
            $oldTotal = $nota->total;
            $oldRekening = $request->old_rekening ?? $nota->idrek;

            // Handle upload bukti nota baru
            $buktiNotaPath = $nota->bukti_nota;
            if ($request->hasFile('bukti_nota')) {
                if ($buktiNotaPath && Storage::disk('public')->exists($buktiNotaPath)) {
                    Storage::disk('public')->delete($buktiNotaPath);
                }
                
                $file = $request->file('bukti_nota');
                $filename = 'konstruksi_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota/konstruksi', $filename, 'public');
            }

            // ROLLBACK LOGIC - jika transaksi lama adalah CASH dan PAID
            $paymentRollbackNeeded = false;
            if ($oldPaymentMethod == 'cash' && $nota->status == 'paid') {
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

            // Simpan PPN jika ada
            if ($ppn > 0) {
                $kodePpn = KodeTransaksi::where('kodetransaksi', '3001')->first();
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
                $kodeDiskon = KodeTransaksi::where('kodetransaksi', '5001')->first();
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
                $logMessage = "Transaksi konstruksi diupdate: " . implode(", ", $changes);
                $this->createUpdateLog($nota->id, $nota->nota_no, $logMessage);
            }

            // Update realisasi anggaran
            $this->updateRealisasiAnggaran($pekerjaanId);

            // PROSES PEMBAYARAN BARU jika transaksi baru adalah CASH
            if ($request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $newTotal, $request->tanggal);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi konstruksi berhasil diupdate',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update construction transaction error:', [
                'error' => $e->getMessage(),
                'pekerjaan_id' => $pekerjaanId,
                'nota_id' => $notaId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View detail transaksi konstruksi
     */
    public function show($pekerjaanId, $notaId)
    {
        try {
            $nota = Nota::with([
                'project',
                'vendor', 
                'rekening',
                'pekerjaanKonstruksi',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                },
                'payments.rekening',
                'cashflows',
                'updateLogs' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])->where('pekerjaan_konstruksi_id', $pekerjaanId)
              ->findOrFail($notaId);

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
     * Delete transaksi konstruksi
     */
    public function destroy($pekerjaanId, $notaId)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
                       ->with(['payments', 'cashflows'])
                       ->findOrFail($notaId);

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
                "Transaksi konstruksi dihapus - No: {$nota->nota_no}, Total: Rp " . number_format($nota->total, 0, ',', '.'));

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

            // Update realisasi anggaran
            $this->updateRealisasiAnggaran($pekerjaanId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi konstruksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete construction transaction error:', [
                'error' => $e->getMessage(),
                'pekerjaan_id' => $pekerjaanId,
                'nota_id' => $notaId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update status nota konstruksi
     */
    public function updateStatus(Request $request, $pekerjaanId, $notaId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'status' => 'required|in:paid,partial,cancel'
            ]);

            $nota = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
                       ->with(['payments'])
                       ->findOrFail($notaId);

            $oldStatus = $nota->status;

            // Buat log untuk perubahan status
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Status diubah: " . ucfirst($oldStatus) . " → " . ucfirst($request->status));

            // Jika status berubah menjadi paid dan payment method cash, buat pembayaran
            if ($request->status == 'paid' && $nota->paymen_method == 'cash' && $oldStatus != 'paid') {
                $this->processCashPayment($nota, $nota->idrek, $nota->total, $nota->tanggal);
            }

            // Jika status berubah dari paid ke status lain dan payment method cash, rollback
            if ($oldStatus == 'paid' && $request->status != 'paid' && $nota->paymen_method == 'cash') {
                $this->rollbackCashPayment($nota);
            }

            $nota->update(['status' => $request->status]);

            // Update realisasi anggaran jika status berubah
            if ($oldStatus != $request->status) {
                $this->updateRealisasiAnggaran($pekerjaanId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Status transaksi berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update status construction transaction error:', [
                'error' => $e->getMessage(),
                'pekerjaan_id' => $pekerjaanId,
                'nota_id' => $notaId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update realisasi anggaran pekerjaan konstruksi
     */
    private function updateRealisasiAnggaran($pekerjaanId)
    {
        try {
            $totalTransaksi = Nota::where('pekerjaan_konstruksi_id', $pekerjaanId)
                ->where('cashflow', 'out')
                ->where('status', 'paid')
                ->sum('total');

            $pekerjaan = PekerjaanKonstruksi::find($pekerjaanId);
            if ($pekerjaan) {
                $pekerjaan->realisasi_anggaran = $totalTransaksi;
                $pekerjaan->save();
            }

            return $totalTransaksi;

        } catch (\Exception $e) {
            \Log::error('Error updating realisasi anggaran:', [
                'pekerjaan_id' => $pekerjaanId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Helper methods (copy from ProjectController with modifications)
     */
    private function createUpdateLog($notaId, $notaNo, $logMessage)
    {
        return TransUpdateLog::create([
            'idnota' => $notaId,
            'nota_no' => $notaNo,
            'update_log' => $logMessage
        ]);
    }

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
                'keterangan' => "Pembayaran nota konstruksi {$nota->nota_no}"
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Cash payment processing error:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    private function rollbackCashPayment($nota)
    {
        try {
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

            return true;

        } catch (\Exception $e) {
            \Log::error('Rollback cash payment error:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    /**
     * Get update logs for a nota
     */
    public function getUpdateLogs($pekerjaanId, $notaId)
    {
        try {
            $logs = TransUpdateLog::where('idnota', $notaId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil log'
            ], 500);
        }
    }

    /**
     * Get saldo rekening
     */
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

    /**
     * Get progress report for construction projects
     */
    public function getReport($pekerjaanId)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with(['project'])->findOrFail($pekerjaanId);
            
            // Get all transactions for this construction
            $transactions = Nota::with(['vendor', 'rekening', 'transactions.kodeTransaksi'])
                ->where('pekerjaan_konstruksi_id', $pekerjaanId)
                ->where('type', 'konstruksi')
                ->where('cashflow', 'out')
                ->orderBy('tanggal', 'desc')
                ->get();

            // Calculate totals
            $totalTransaksi = $transactions->sum('total');
            $totalPaid = $transactions->where('status', 'paid')->sum('total');
            $totalOpen = $transactions->where('status', 'open')->sum('total');
            $totalCancel = $transactions->where('status', 'cancel')->sum('total');

            return response()->json([
                'success' => true,
                'data' => [
                    'pekerjaan' => $pekerjaan,
                    'transactions' => $transactions,
                    'summary' => [
                        'total_anggaran' => $pekerjaan->anggaran,
                        'total_transaksi' => $totalTransaksi,
                        'total_paid' => $totalPaid,
                        'total_open' => $totalOpen,
                        'total_cancel' => $totalCancel,
                        'sisa_anggaran' => $pekerjaan->anggaran - $totalTransaksi,
                        'persentase_terpakai' => $pekerjaan->anggaran > 0 ? 
                            round(($totalTransaksi / $pekerjaan->anggaran) * 100, 2) : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting construction report:', [
                'pekerjaan_id' => $pekerjaanId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }
}