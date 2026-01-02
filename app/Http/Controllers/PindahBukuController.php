<?php

namespace App\Http\Controllers;

use App\Models\TransaksiPindahBuku;
use App\Models\TransaksiPindahBukuLog;
use App\Models\Rekening;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PindahBukuController extends Controller
{
    // Halaman index pindah buku
    public function index()
    {
        return view('transaksi.pindahbuku.index');
    }

    // Datatable untuk transaksi pindah buku
    public function getdata()
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return DataTables::collection(collect([]))->toJson();
        }

        $query = TransaksiPindahBuku::with([
            'rekeningAsal:idrek,norek,namarek,saldo',
            'rekeningTujuan:idrek,norek,namarek,saldo',
            'creator:id,name'
        ])
        ->whereHas('rekeningAsal', function($q) use ($companyId) {
            $q->where('idcompany', $companyId);
        })
        ->orWhereHas('rekeningTujuan', function($q) use ($companyId) {
            $q->where('idcompany', $companyId);
        });

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
            ->addColumn('rekening_asal', function($row) {
                return $row->rekeningAsal ? 
                    $row->rekeningAsal->norek . ' - ' . $row->rekeningAsal->namarek : '-';
            })
            ->addColumn('rekening_tujuan', function($row) {
                return $row->rekeningTujuan ? 
                    $row->rekeningTujuan->norek . ' - ' . $row->rekeningTujuan->namarek : '-';
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('nominal', function($row) {
                return 'Rp ' . number_format($row->nominal, 0, ',', '.');
            })
            ->editColumn('status', function($row) {
                $badge = [
                    'pending' => 'bg-warning',
                    'completed' => 'bg-success',
                    'failed' => 'bg-danger'
                ];
                return '<span class="badge '.$badge[$row->status].'">'.ucfirst($row->status).'</span>';
            })
            ->addColumn('user', function($row) {
                return $row->creator ? $row->creator->name : '-';
            })
            ->filter(function($query) use ($companyId) {
                $search = request('search.value');
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('kode_transaksi', 'like', "%{$search}%")
                        ->orWhere('nominal', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%")
                        ->orWhereHas('rekeningAsal', function($q) use ($search) {
                            $q->where('norek', 'like', "%{$search}%")
                              ->orWhere('namarek', 'like', "%{$search}%");
                        })
                        ->orWhereHas('rekeningTujuan', function($q) use ($search) {
                            $q->where('norek', 'like', "%{$search}%")
                              ->orWhere('namarek', 'like', "%{$search}%");
                        });
                    });
                }
                
                $query->orderBy('tanggal', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(1000);
            })
            ->order(function($query) {
                $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            })
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Simpan transaksi pindah buku
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'rekening_asal_id' => 'required|exists:rekening,idrek',
                'rekening_tujuan_id' => 'required|exists:rekening,idrek|different:rekening_asal_id',
                'nominal' => 'required|numeric|min:1',
                'keterangan' => 'nullable|string|max:500',
                'tanggal' => 'required|date'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            if (!$companyId) {
                throw new \Exception("Company aktif tidak ditemukan");
            }

            // Validasi rekening milik company yang sama
            $rekeningAsal = Rekening::where('idrek', $request->rekening_asal_id)
                ->where('idcompany', $companyId)
                ->first();

            $rekeningTujuan = Rekening::where('idrek', $request->rekening_tujuan_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekeningAsal || !$rekeningTujuan) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik PT ini");
            }

            // Validasi saldo mencukupi
            if ($rekeningAsal->saldo < $request->nominal) {
                throw new \Exception(
                    "Saldo rekening asal tidak mencukupi. Saldo tersedia: Rp " .
                    number_format($rekeningAsal->saldo, 0, ',', '.')
                );
            }

            // Generate kode transaksi
            $kodeTransaksi = 'PBK-' . $companyId . '-' . date('Ymd') . '-' . rand(1000, 9999);

            // ==========================
            // SIMPAN TRANSAKSI PBK
            // ==========================
            $transaksi = TransaksiPindahBuku::create([
                'kode_transaksi'     => $kodeTransaksi,
                'rekening_asal_id'   => $request->rekening_asal_id,
                'rekening_tujuan_id' => $request->rekening_tujuan_id,
                'nominal'            => $request->nominal,
                'keterangan'         => $request->keterangan,
                'tanggal'            => $request->tanggal,
                'status'             => 'pending',
                'idcompany'          => $companyId, 

                'created_by'         => $user->id
            ]);

            // Proses transfer saldo rekening
            $this->processTransfer($transaksi);

            // Update status menjadi completed
            $transaksi->update(['status' => 'completed']);

            // Buat log
            $this->createLog(
                $transaksi->id,
                'create',
                "Transaksi pindah buku berhasil dibuat: {$kodeTransaksi}, " .
                "Rekening Asal: {$rekeningAsal->norek} - {$rekeningAsal->namarek}, " .
                "Rekening Tujuan: {$rekeningTujuan->norek} - {$rekeningTujuan->namarek}, " .
                "Nominal: Rp " . number_format($request->nominal, 0, ',', '.')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pindah buku berhasil disimpan',
                'data' => $transaksi
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error store pindah buku:', [
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

    // Proses transfer dana
    private function processTransfer($transaksi)
    {
        try {
            // Ambil data rekening
            $rekeningAsal = Rekening::find($transaksi->rekening_asal_id);
            $rekeningTujuan = Rekening::find($transaksi->rekening_tujuan_id);

            if (!$rekeningAsal || !$rekeningTujuan) {
                throw new \Exception("Rekening tidak ditemukan");
            }

            $saldoAsalAwal = $rekeningAsal->saldo;
            $saldoTujuanAwal = $rekeningTujuan->saldo;

            // Validasi saldo asal mencukupi
            if ($saldoAsalAwal < $transaksi->nominal) {
                throw new \Exception("Saldo rekening asal tidak mencukupi");
            }

            // Update saldo rekening asal (pengurangan)
            $rekeningAsal->saldo -= $transaksi->nominal;
            $rekeningAsal->save();

            // Update saldo rekening tujuan (penambahan)
            $rekeningTujuan->saldo += $transaksi->nominal;
            $rekeningTujuan->save();

            // Catat cashflow untuk rekening asal
            Cashflow::create([
                'idrek' => $rekeningAsal->idrek,
                'tanggal' => $transaksi->tanggal,
                'cashflow' => 'out',
                'nominal' => $transaksi->nominal,
                'saldo_awal' => $saldoAsalAwal,
                'saldo_akhir' => $rekeningAsal->saldo,
                'keterangan' => "Transfer ke {$rekeningTujuan->norek} - {$rekeningTujuan->namarek}: " . 
                              $transaksi->keterangan,
                'kode_transaksi' => $transaksi->kode_transaksi
            ]);

            // Catat cashflow untuk rekening tujuan
            Cashflow::create([
                'idrek' => $rekeningTujuan->idrek,
                'tanggal' => $transaksi->tanggal,
                'cashflow' => 'in',
                'nominal' => $transaksi->nominal,
                'saldo_awal' => $saldoTujuanAwal,
                'saldo_akhir' => $rekeningTujuan->saldo,
                'keterangan' => "Transfer dari {$rekeningAsal->norek} - {$rekeningAsal->namarek}: " . 
                              $transaksi->keterangan,
                'kode_transaksi' => $transaksi->kode_transaksi
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Error process transfer:', [
                'transaksi_id' => $transaksi->id,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Rollback transfer
    private function rollbackTransfer($transaksi)
    {
        try {
            // Ambil data rekening
            $rekeningAsal = Rekening::find($transaksi->rekening_asal_id);
            $rekeningTujuan = Rekening::find($transaksi->rekening_tujuan_id);

            if (!$rekeningAsal || !$rekeningTujuan) {
                throw new \Exception("Rekening tidak ditemukan untuk rollback");
            }

            // Kembalikan saldo rekening asal (tambahkan)
            $rekeningAsal->saldo += $transaksi->nominal;
            $rekeningAsal->save();

            // Kurangi saldo rekening tujuan (kembalikan)
            $rekeningTujuan->saldo -= $transaksi->nominal;
            $rekeningTujuan->save();

            // Hapus cashflow terkait
            Cashflow::where('kode_transaksi', $transaksi->kode_transaksi)->delete();

            return true;

        } catch (\Exception $e) {
            \Log::error('Error rollback transfer:', [
                'transaksi_id' => $transaksi->id,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Tampilkan detail transaksi
    public function show($id)
    {
        try {
            $transaksi = TransaksiPindahBuku::with([
                'rekeningAsal',
                'rekeningTujuan',
                'creator',
                'logs' => function($q) {
                    $q->with('user:id,name')
                      ->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaksi
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    // Ambil data untuk edit
    public function edit($id)
    {
        try {
            $transaksi = TransaksiPindahBuku::with(['rekeningAsal', 'rekeningTujuan'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaksi
            ]);

        } catch (\Exception $e) {
            \Log::error('Error edit pindah buku:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
    }

    // Update transaksi pindah buku
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'rekening_asal_id' => 'required|exists:rekening,idrek',
                'rekening_tujuan_id' => 'required|exists:rekening,idrek|different:rekening_asal_id',
                'nominal' => 'required|numeric|min:1',
                'keterangan' => 'nullable|string|max:500',
                'tanggal' => 'required|date'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            // Cari transaksi lama
            $transaksi = TransaksiPindahBuku::findOrFail($id);
            
            if ($transaksi->status !== 'pending') {
                throw new \Exception("Hanya transaksi dengan status pending yang dapat diubah");
            }

            // Simpan data lama untuk log
            $oldData = $transaksi->toArray();

            // Rollback transfer lama jika sudah diproses
            if ($transaksi->status === 'completed') {
                $this->rollbackTransfer($transaksi);
            }

            // Validasi rekening baru
            $rekeningAsal = Rekening::where('idrek', $request->rekening_asal_id)
                ->where('idcompany', $companyId)
                ->first();

            $rekeningTujuan = Rekening::where('idrek', $request->rekening_tujuan_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekeningAsal || !$rekeningTujuan) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik PT ini");
            }

            // Validasi saldo baru mencukupi
            if ($rekeningAsal->saldo < $request->nominal) {
                throw new \Exception("Saldo rekening asal tidak mencukupi");
            }

            // Update transaksi
            $transaksi->update([
                'rekening_asal_id' => $request->rekening_asal_id,
                'rekening_tujuan_id' => $request->rekening_tujuan_id,
                'nominal' => $request->nominal,
                'keterangan' => $request->keterangan,
                'tanggal' => $request->tanggal
            ]);

            // Proses transfer baru
            $this->processTransfer($transaksi);
            $transaksi->update(['status' => 'completed']);

            // Buat log perubahan
            $changes = $this->getChangesForLog($oldData, $transaksi->toArray());
            if (!empty($changes)) {
                $logMessage = "Transaksi pindah buku diupdate: " . implode(", ", $changes);
                $this->createLog($transaksi->id, 'update', $logMessage);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pindah buku berhasil diupdate',
                'data' => $transaksi
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error update pindah buku:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Hapus transaksi pindah buku
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaksi = TransaksiPindahBuku::findOrFail($id);
            $user = Auth::user();

            // Cek role user
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus transaksi'
                ], 403);
            }

            // Rollback transfer jika status completed
            if ($transaksi->status === 'completed') {
                $this->rollbackTransfer($transaksi);
            }

            // Buat log penghapusan
            $this->createLog($transaksi->id, 'delete', 
                "Transaksi pindah buku dihapus: {$transaksi->kode_transaksi}, " .
                "Nominal: Rp " . number_format($transaksi->nominal, 0, ',', '.'));

            // Hapus transaksi
            $transaksi->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pindah buku berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error delete pindah buku:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Ambil saldo rekening
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
            \Log::error('Error getting saldo rekening:', [
                'rekening_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['saldo' => 0]);
        }
    }

    // Buat log
    private function createLog($pindahBukuId, $logType, $description)
    {
        return TransaksiPindahBukuLog::create([
            'pindah_buku_id' => $pindahBukuId,
            'log_type' => $logType,
            'description' => $description,
            'created_by' => Auth::id()
        ]);
    }

    // Dapatkan perubahan untuk log
    private function getChangesForLog($oldData, $newData)
    {
        $changes = [];
        
        $fields = [
            'rekening_asal_id' => 'Rekening Asal',
            'rekening_tujuan_id' => 'Rekening Tujuan',
            'nominal' => 'Nominal',
            'keterangan' => 'Keterangan',
            'tanggal' => 'Tanggal',
            'status' => 'Status'
        ];
        
        foreach ($fields as $field => $label) {
            if (isset($oldData[$field]) && isset($newData[$field])) {
                if ($oldData[$field] != $newData[$field]) {
                    $oldValue = $oldData[$field];
                    $newValue = $newData[$field];
                    
                    // Format khusus untuk beberapa field
                    if (in_array($field, ['rekening_asal_id', 'rekening_tujuan_id'])) {
                        $oldRek = Rekening::find($oldValue);
                        $newRek = Rekening::find($newValue);
                        $oldValue = $oldRek ? $oldRek->norek . ' - ' . $oldRek->namarek : 'Tidak ada';
                        $newValue = $newRek ? $newRek->norek . ' - ' . $newRek->namarek : 'Tidak ada';
                    } elseif ($field == 'nominal') {
                        $oldValue = 'Rp ' . number_format($oldValue, 0, ',', '.');
                        $newValue = 'Rp ' . number_format($newValue, 0, ',', '.');
                    } elseif ($field == 'tanggal') {
                        $oldValue = date('d/m/Y', strtotime($oldValue));
                        $newValue = date('d/m/Y', strtotime($newValue));
                    }
                    
                    $changes[] = "{$label} diubah: {$oldValue} → {$newValue}";
                }
            }
        }
        
        return $changes;
    }
}