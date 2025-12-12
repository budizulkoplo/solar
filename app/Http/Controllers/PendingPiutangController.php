<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\NotaPayment;
use App\Models\Angsuran;
use App\Models\Cashflow;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PendingPiutangController extends Controller
{
    /**
     * Halaman daftar pending pembayaran (in - open)
     */
    public function pendingPembayaran()
    {
        $rekenings = Rekening::where('idproject', session('active_project_id'))
            ->orderBy('norek')
            ->get();
        
        return view('transaksi.pending.pembayaran', [
            'rekenings' => $rekenings,
            'type' => 'pembayaran'
        ]);
    }

    /**
     * Halaman daftar piutang (out - open)
     */
    public function piutang()
    {
        $rekenings = Rekening::where('idproject', session('active_project_id'))
            ->orderBy('norek')
            ->get();
        
        return view('transaksi.pending.piutang', [
            'rekenings' => $rekenings,
            'type' => 'piutang'
        ]);
    }

    /**
     * Datatable untuk pending pembayaran (transaksi masuk dengan status open)
     */
    public function getPendingPembayaran()
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor',
                'rekening:idrek,norek,namarek',
                'angsuran' => function($q) {
                    $q->orderBy('tanggal', 'asc');
                }
            ])
            ->where('cashflow', 'in')
            ->whereIn('paymen_method', ['tempo'])
            ->where('idproject', session('active_project_id'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success bayar-btn" data-id="'.$row->id.'">
                        <i class="bi bi-cash"></i> Bayar
                    </button>
                </div>';
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('tgl_tempo', function($row) {
                return $row->tgl_tempo ? date('d/m/Y', strtotime($row->tgl_tempo)) : '-';
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->addColumn('terbayar', function($row) {
                $totalTerbayar = $row->angsuran->sum('jumlah');
                return 'Rp ' . number_format($totalTerbayar, 0, ',', '.');
            })
            ->addColumn('sisa', function($row) {
                $totalTerbayar = $row->angsuran->sum('jumlah');
                $sisa = $row->total - $totalTerbayar;
                return 'Rp ' . number_format($sisa, 0, ',', '.');
            })
            ->addColumn('angsuran_count', function($row) {
                return $row->angsuran->count();
            })
            ->filter(function($query) {
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
                        ->orWhereHas('project', function($q) use ($search) {
                            $q->where('namaproject', 'like', "%{$search}%");
                        });
                    });
                } else {
                    $query->orderBy('tgl_tempo', 'asc')
                        ->orderBy('tanggal', 'asc')
                        ->limit(1000);
                }
            })
            ->order(function($query) {
                $query->orderBy('tgl_tempo', 'asc')
                    ->orderBy('tanggal', 'asc');
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    /**
     * Datatable untuk piutang (transaksi keluar dengan status open)
     */
    public function getPiutang()
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor',
                'rekening:idrek,norek,namarek',
                'angsuran' => function($q) {
                    $q->orderBy('tanggal', 'asc');
                }
            ])
            ->where('cashflow', 'out')
            ->whereIn('paymen_method', ['tempo'])
            ->where('idproject', session('active_project_id'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success bayar-btn" data-id="'.$row->id.'">
                        <i class="bi bi-cash"></i> Bayar
                    </button>
                </div>';
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('tgl_tempo', function($row) {
                return $row->tgl_tempo ? date('d/m/Y', strtotime($row->tgl_tempo)) : '-';
            })
            ->editColumn('total', function($row) {
                return 'Rp ' . number_format($row->total, 0, ',', '.');
            })
            ->addColumn('terbayar', function($row) {
                $totalTerbayar = $row->angsuran->sum('jumlah');
                return 'Rp ' . number_format($totalTerbayar, 0, ',', '.');
            })
            ->addColumn('sisa', function($row) {
                $totalTerbayar = $row->angsuran->sum('jumlah');
                $sisa = $row->total - $totalTerbayar;
                return 'Rp ' . number_format($sisa, 0, ',', '.');
            })
            ->addColumn('angsuran_count', function($row) {
                return $row->angsuran->count();
            })
            ->filter(function($query) {
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
                        ->orWhereHas('project', function($q) use ($search) {
                            $q->where('namaproject', 'like', "%{$search}%");
                        });
                    });
                } else {
                    $query->orderBy('tgl_tempo', 'asc')
                        ->orderBy('tanggal', 'asc')
                        ->limit(1000);
                }
            })
            ->order(function($query) {
                $query->orderBy('tgl_tempo', 'asc')
                    ->orderBy('tanggal', 'asc');
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    /**
     * Show detail nota untuk pending/piutang
     */
    public function show($id)
    {
        try {
            $nota = Nota::with([
                'project',
                'vendor', 
                'rekening',
                'transactions' => function($q) {
                    $q->with('kodeTransaksi')
                      ->orderBy('id');
                },
                'angsuran' => function($q) {
                    $q->orderBy('tanggal', 'asc')
                      ->with('rekening');
                }
            ])->findOrFail($id);

            $totalTerbayar = $nota->angsuran->sum('jumlah');
            $sisa = $nota->total - $totalTerbayar;

            return response()->json([
                'success' => true,
                'data' => $nota,
                'summary' => [
                    'total' => $nota->total,
                    'terbayar' => $totalTerbayar,
                    'sisa' => $sisa
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Proses pembayaran cicilan/angsuran
     */
    public function bayar(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'tanggal_bayar' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'jumlah_bayar' => 'required|numeric|min:1',
                'keterangan' => 'nullable|string|max:255'
            ]);

            $nota = Nota::with(['angsuran'])->findOrFail($id);

            // Hitung total yang sudah dibayar
            $totalTerbayar = $nota->angsuran->sum('jumlah');
            $sisa = $nota->total - $totalTerbayar;

            // Validasi jumlah bayar tidak melebihi sisa
            if ($request->jumlah_bayar > $sisa) {
                throw new \Exception("Jumlah pembayaran melebihi sisa hutang/piutang. Sisa: Rp " . number_format($sisa, 0, ',', '.'));
            }

            // Proses pembayaran berdasarkan cashflow
            $jumlahBayar = $request->jumlah_bayar;
            
            // Update saldo rekening berdasarkan cashflow
            $rekening = Rekening::find($request->idrek);
            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan");
            }

            $saldoAwal = $rekening->saldo;
            
            // Logika pembayaran:
            // Jika cashflow = 'in' (piutang usaha -> kita terima uang): saldo bertambah
            // Jika cashflow = 'out' (hutang usaha -> kita bayar): saldo berkurang
            if ($nota->cashflow == 'in') {
                // Piutang: kita menerima pembayaran
                $rekening->saldo += $jumlahBayar;
                $keteranganCashflow = "Penerimaan angsuran nota {$nota->nota_no} - {$request->keterangan}";
            } else {
                // Hutang: kita membayar
                $rekening->saldo -= $jumlahBayar;
                $keteranganCashflow = "Pembayaran angsuran nota {$nota->nota_no} - {$request->keterangan}";
            }
            
            $rekening->save();

            // Simpan angsuran
            $angsuran = Angsuran::create([
                'idnota' => $nota->id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal_bayar,
                'jumlah' => $jumlahBayar,
                'keterangan' => $request->keterangan ?? 'Pembayaran angsuran'
            ]);

            // Catat di cashflow
            Cashflow::create([
                'idrek' => $request->idrek,
                'idnota' => $nota->id,
                'tanggal' => $request->tanggal_bayar,
                'cashflow' => $nota->cashflow,
                'nominal' => $jumlahBayar,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => $keteranganCashflow
            ]);

            // Update total terbayar di nota payment jika ada, atau buat baru
            $notaPayment = NotaPayment::where('idnota', $nota->id)->first();
            if ($notaPayment) {
                // Update existing payment dengan total baru
                $notaPayment->update([
                    'jumlah' => $totalTerbayar + $jumlahBayar
                ]);
            } else {
                // Buat payment baru
                NotaPayment::create([
                    'idnota' => $nota->id,
                    'idrek' => $request->idrek,
                    'tanggal' => $request->tanggal_bayar,
                    'jumlah' => $jumlahBayar
                ]);
            }

            // Cek apakah sudah lunas
            $totalTerbayarBaru = $totalTerbayar + $jumlahBayar;
            if ($totalTerbayarBaru >= $nota->total) {
                $nota->update(['status' => 'paid']);
                $statusNota = "LUNAS";
            } else if ($totalTerbayarBaru > 0) {
                $nota->update(['status' => 'partial']);
                $statusNota = "SEBAGIAN";
            }

            // Buat log transaksi
            $logMessage = "Pembayaran angsuran: Rp " . number_format($jumlahBayar, 0, ',', '.') . 
                        " via " . $rekening->norek . " - " . $rekening->namarek . 
                        ". Total terbayar: Rp " . number_format($totalTerbayarBaru, 0, ',', '.') .
                        " dari total Rp " . number_format($nota->total, 0, ',', '.');
            
            \App\Models\TransUpdateLog::create([
                'idnota' => $nota->id,
                'nota_no' => $nota->nota_no,
                'update_log' => $logMessage
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil disimpan. Status: ' . ($statusNota ?? 'BELUM LUNAS'),
                'data' => [
                    'angsuran_id' => $angsuran->id,
                    'total_terbayar' => $totalTerbayarBaru,
                    'sisa' => $nota->total - $totalTerbayarBaru,
                    'status' => $nota->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment processing error:', [
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
     * Get angsuran history untuk nota tertentu
     */
    public function getAngsuranHistory($id)
    {
        try {
            $angsuran = Angsuran::with('rekening')
                ->where('idnota', $id)
                ->orderBy('tanggal', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $angsuran
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat angsuran'
            ], 500);
        }
    }

    /**
     * Hapus angsuran (rollback pembayaran)
     */
    public function hapusAngsuran($id)
    {
        DB::beginTransaction();
        try {
            $angsuran = Angsuran::with(['nota', 'rekening'])->findOrFail($id);
            $nota = $angsuran->nota;

            // Cek role user
            $user = auth()->user();
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus angsuran'
                ], 403);
            }

            // Rollback saldo rekening
            $rekening = $angsuran->rekening;
            $saldoAwal = $rekening->saldo;
            
            // Logika rollback:
            // Jika cashflow = 'in' (piutang): saldo dikurangi (karena sebelumnya bertambah)
            // Jika cashflow = 'out' (hutang): saldo ditambah (karena sebelumnya berkurang)
            if ($nota->cashflow == 'in') {
                $rekening->saldo -= $angsuran->jumlah;
                $keterangan = "Rollback penerimaan angsuran nota {$nota->nota_no}";
            } else {
                $rekening->saldo += $angsuran->jumlah;
                $keterangan = "Rollback pembayaran angsuran nota {$nota->nota_no}";
            }
            
            $rekening->save();

            // Hapus cashflow terkait
            Cashflow::where('idnota', $nota->id)
                ->where('tanggal', $angsuran->tanggal)
                ->where('nominal', $angsuran->jumlah)
                ->delete();

            // Hapus angsuran
            $angsuran->delete();

            // Update status nota berdasarkan sisa pembayaran
            $totalTerbayar = Angsuran::where('idnota', $nota->id)->sum('jumlah');
            
            if ($totalTerbayar >= $nota->total) {
                $statusBaru = 'paid';
            } else if ($totalTerbayar > 0) {
                $statusBaru = 'partial';
            } else {
                $statusBaru = 'open';
            }
            
            $nota->update(['status' => $statusBaru]);

            // Buat log
            $logMessage = "Angsuran dihapus: Rp " . number_format($angsuran->jumlah, 0, ',', '.') . 
                        ". Total terbayar sekarang: Rp " . number_format($totalTerbayar, 0, ',', '.') .
                        ". Status: " . $statusBaru;
            
            \App\Models\TransUpdateLog::create([
                'idnota' => $nota->id,
                'nota_no' => $nota->nota_no,
                'update_log' => $logMessage
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Angsuran berhasil dihapus',
                'data' => [
                    'total_terbayar' => $totalTerbayar,
                    'status' => $statusBaru
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Delete angsuran error:', [
                'error' => $e->getMessage(),
                'angsuran_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export laporan pending pembayaran/piutang
     */
    public function exportReport($type)
    {
        try {
            $query = Nota::with([
                    'project',
                    'vendor',
                    'rekening',
                    'angsuran'
                ])
                ->where('cashflow', $type)
                ->where('status', '!=', 'paid')
                ->where('idproject', session('active_project_id'))
                ->orderBy('tgl_tempo', 'asc')
                ->get();

            $data = [];
            foreach ($query as $nota) {
                $totalTerbayar = $nota->angsuran->sum('jumlah');
                $sisa = $nota->total - $totalTerbayar;
                
                $data[] = [
                    'Nomor Nota' => $nota->nota_no,
                    'Tanggal' => date('d/m/Y', strtotime($nota->tanggal)),
                    'Jatuh Tempo' => $nota->tgl_tempo ? date('d/m/Y', strtotime($nota->tgl_tempo)) : '-',
                    'Nama Transaksi' => $nota->namatransaksi,
                    'Vendor' => $nota->vendor ? $nota->vendor->namavendor : '-',
                    'Project' => $nota->project ? $nota->project->namaproject : '-',
                    'Total' => $nota->total,
                    'Terbayar' => $totalTerbayar,
                    'Sisa' => $sisa,
                    'Status' => ucfirst($nota->status)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}