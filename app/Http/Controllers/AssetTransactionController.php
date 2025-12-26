<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\AssetMutation;
use App\Models\Nota;
use App\Models\NotaTransaction;
use App\Models\KodeTransaksi;
use App\Models\Project;
use App\Models\Rekening;
use App\Models\Cashflow;
use App\Models\NotaPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AssetTransactionController extends Controller
{
    // Halaman transaksi aset
    public function index()
    {
        return view('transaksi.asset.index');
    }

    // Datatable untuk transaksi aset
    public function getdata()
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor',
                'transactions' => function($q) {
                    $q->whereHas('kodeTransaksi', function($q2) {
                        $q2->where('kodetransaksi', 'like', '4000%');
                    });
                }
            ])
            ->whereHas('transactions', function($q) {
                $q->whereHas('kodeTransaksi', function($q2) {
                    $q2->where('kodetransaksi', 'like', '4000%');
                });
            })
            ->where('cashflow', 'out')
            ->where('idproject', session('active_project_id'));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $btnViewAsset = '';
                $btnGenerateAsset = '';
                
                // Cek apakah sudah ada aset yang digenerate
                $hasAsset = Asset::where('idnota', $row->id)->exists();
                
                if ($hasAsset) {
                    $btnViewAsset = '<button class="btn btn-sm btn-info view-asset-btn" data-id="'.$row->id.'" data-asset="true">
                        <i class="bi bi-box-seam"></i> Lihat Aset
                    </button>';
                } else {
                    $btnGenerateAsset = '<button class="btn btn-sm btn-success generate-asset-btn" data-id="'.$row->id.'">
                        <i class="bi bi-magic"></i> Generate Aset
                    </button>';
                }
                
                $deleteBtn = $canDelete ? 
                    '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>' :
                    '<button class="btn btn-sm btn-danger" disabled><i class="bi bi-trash"></i></button>';

                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>
                    '.$btnGenerateAsset.'
                    '.$btnViewAsset.'
                    '.$deleteBtn.'
                </div>';
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
            ->addColumn('aset_status', function($row) {
                $hasAsset = Asset::where('idnota', $row->id)->exists();
                return $hasAsset ? 
                    '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Sudah Aset</span>' :
                    '<span class="badge bg-warning"><i class="bi bi-clock"></i> Belum Aset</span>';
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
                    $query->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->limit(1000);
                }
            })
            ->order(function($query) {
                $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
            })
            ->rawColumns(['action', 'status', 'aset_status'])
            ->toJson();
    }

    // Form untuk transaksi aset baru
    public function create()
    {
        return view('transaksi.asset.create');
    }

    // Simpan transaksi aset
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'vendor_id' => 'required|exists:vendors,id',
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:1',
                'bukti_nota' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'ppn' => 'nullable|numeric|min:0',
                'diskon' => 'nullable|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                // Data aset
                'assets' => 'required|array|min:1',
                'assets.*.nama_aset' => 'required|string|max:255',
                'assets.*.tanggal_mulai_susut' => 'required|date',
                'assets.*.umur_ekonomis' => 'required|integer|min:1',
                'assets.*.nilai_residu' => 'nullable|numeric|min:0',
                'assets.*.metode_penyusutan' => 'required|in:garis_lurus,saldo_menurun',
                'assets.*.persentase_susut' => 'nullable|numeric|min:0|max:100',
                'assets.*.lokasi' => 'nullable|string|max:100',
                'assets.*.pic' => 'nullable|string|max:100',
                'assets.*.keterangan' => 'nullable|string',
            ]);

            // Ambil user dan project
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;
            
            $projectId = session('active_project_id');
            $project = Project::find($projectId);
            
            if (!$project) {
                throw new \Exception("Project tidak ditemukan");
            }

            $idcompany = $project->idcompany ?? session('active_project_company_id');
            $idretail = $project->idretail;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_asset_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            // Hitung total
            $subtotal = $request->subtotal ?? 0;
            $ppn = $request->ppn ?? 0;
            $diskon = $request->diskon ?? 0;
            $total = $subtotal + $ppn - $diskon;

            // Simpan nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => $project->id,
                'idcompany' => $idcompany,
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
                'status' => $request->paymen_method == 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
                'is_asset_transaction' => true, // Flag untuk transaksi aset
            ];

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

            // Simpan data aset
            foreach ($request->assets as $assetData) {
                // Ambil kode transaksi dari detail nota yang sesuai
                $kodeTransaksi = KodeTransaksi::find($request->transactions[0]['idkodetransaksi']);
                
                // Hitung harga perolehan per aset (jika lebih dari 1 aset)
                $totalAssets = count($request->assets);
                $hargaPerolehanPerAsset = $total / $totalAssets;
                
                $asset = Asset::create([
                    'kode_aset' => Asset::generateKodeAset(session('active_project_id')),
                    'nama_aset' => $assetData['nama_aset'],
                    'idkodetransaksi' => $kodeTransaksi->id,
                    'kodetransaksi' => $kodeTransaksi->kodetransaksi,
                    'tanggal_pembelian' => $request->tanggal,
                    'tanggal_mulai_susut' => $assetData['tanggal_mulai_susut'],
                    'harga_perolehan' => $hargaPerolehanPerAsset,
                    'nilai_residu' => $assetData['nilai_residu'] ?? 0,
                    'umur_ekonomis' => $assetData['umur_ekonomis'],
                    'metode_penyusutan' => $assetData['metode_penyusutan'],
                    'persentase_susut' => $assetData['persentase_susut'] ?? null,
                    'lokasi' => $assetData['lokasi'] ?? null,
                    'pic' => $assetData['pic'] ?? null,
                    'keterangan' => $assetData['keterangan'] ?? null,
                    'idcompany' => $idcompany,
                    'idproject' => $projectId,
                    'idretail' => $idretail,
                    'idnota' => $nota->id, // Link ke nota
                ]);

                // Generate schedule penyusutan pertama
                $this->generateFirstDepreciation($asset);
            }

            // Jika cash, proses pembayaran
            if ($request->paymen_method == 'cash') {
                $this->processCashPayment($nota, $request->idrek, $total, $request->tanggal);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi aset berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Asset Transaction Error:', [
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

    // Generate aset dari transaksi existing
    public function generateAssetFromNota($notaId)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::with(['transactions.kodeTransaksi'])->findOrFail($notaId);
            
            // Cek apakah sudah ada aset
            $existingAsset = Asset::where('idnota', $notaId)->first();
            if ($existingAsset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aset sudah digenerate dari nota ini'
                ]);
            }

            // Cari transaksi dengan kode 4000 (Penambahan Aset)
            $assetTransaction = $nota->transactions->first(function($transaction) {
                return $transaction->kodeTransaksi && 
                       str_starts_with($transaction->kodeTransaksi->kodetransaksi, '4000');
            });

            if (!$assetTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ditemukan transaksi dengan kode aset (4000)'
                ]);
            }

            $project = Project::find($nota->idproject);
            $projectId = session('active_project_id');
            // Buat aset tunggal
            $asset = Asset::create([
                'kode_aset' => Asset::generateKodeAset(session('active_project_id')),
                'nama_aset' => $nota->namatransaksi,
                'idkodetransaksi' => $assetTransaction->idkodetransaksi,
                'kodetransaksi' => $assetTransaction->kodeTransaksi->kodetransaksi,
                'tanggal_pembelian' => $nota->tanggal,
                'tanggal_mulai_susut' => $nota->tanggal, // mulai susut dari tanggal pembelian
                'harga_perolehan' => $nota->total,
                'nilai_residu' => $nota->total * 0.1, // 10% dari harga perolehan
                'umur_ekonomis' => 60, // 5 tahun (60 bulan) default
                'metode_penyusutan' => 'garis_lurus',
                'lokasi' => 'Gudang',
                'pic' => $nota->namauser,
                'keterangan' => 'Generated from nota: ' . $nota->nota_no,
                'idcompany' => $nota->idcompany,
                'idproject' => $nota->idproject,
                'idretail' => $nota->idretail,
                'idnota' => $nota->id,
            ]);

            // Generate schedule penyusutan
            $this->generateFirstDepreciation($asset);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Aset berhasil digenerate',
                'asset_id' => $asset->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Generate Asset Error:', [
                'message' => $e->getMessage(),
                'nota_id' => $notaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Generate schedule penyusutan pertama
    private function generateFirstDepreciation(Asset $asset)
    {
        $bulanPertama = $asset->tanggal_mulai_susut;
        $bulanPertama->setDay(1); // Set ke tanggal 1 setiap bulan
        
        $nilaiPenyusutan = $asset->calculateMonthlyDepreciation();
        
        AssetDepreciation::create([
            'asset_id' => $asset->id,
            'periode' => $bulanPertama->format('Y-m-01'),
            'bulan_ke' => 1,
            'nilai_penyusutan' => $nilaiPenyusutan,
            'akumulasi_penyusutan' => $nilaiPenyusutan,
            'nilai_buku' => $asset->harga_perolehan - $nilaiPenyusutan,
            'status' => 'terbentuk',
            'keterangan' => 'Penyusutan pertama'
        ]);
    }

    // Ambil detail aset
    public function getAssetDetails($notaId)
    {
        try {
            $assets = Asset::with(['depreciations' => function($q) {
                $q->orderBy('periode', 'asc');
            }])
            ->where('idnota', $notaId)
            ->get();
            
            $nota = Nota::find($notaId);

            return response()->json([
                'success' => true,
                'assets' => $assets,
                'nota' => $nota
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data aset'
            ], 500);
        }
    }

    // Process cash payment (sama seperti di ProjectController)
    private function processCashPayment($nota, $idrek, $jumlah, $tanggal)
    {
        try {
            $rekening = Rekening::find($idrek);
            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan");
            }

            $saldoAwal = $rekening->saldo;
            $rekening->saldo -= $jumlah; // Out transaction
            $rekening->save();

            NotaPayment::create([
                'idnota' => $nota->id,
                'idrek' => $idrek,
                'tanggal' => $tanggal,
                'jumlah' => $jumlah
            ]);

            Cashflow::create([
                'idrek' => $idrek,
                'idnota' => $nota->id,
                'tanggal' => $tanggal,
                'cashflow' => 'out',
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran aset {$nota->nota_no}"
            ]);

            return true;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    // Halaman daftar aset
    public function assetList()
    {
        return view('transaksi.asset.list');
    }

    // Datatable untuk daftar aset
    public function getAssetData(Request $request)
    {
        $query = Asset::with(['project', 'kodeTransaksi', 'depreciations' => function($q) {
                $q->where('status', 'terposting');
            }])
            ->where('idproject', session('active_project_id'));

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('metode')) {
            $query->where('metode_penyusutan', $request->metode);
        }
        
        if ($request->filled('date_from')) {
            $query->where('tanggal_pembelian', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->where('tanggal_pembelian', '<=', $request->date_to);
        }

        // If only summary requested
        if ($request->has('summary_only')) {
            $assets = $query->get();
            
            $summary = [
                'total_assets' => $assets->count(),
                'total_value' => $assets->sum('harga_perolehan'),
                'total_book_value' => $assets->sum(function($asset) {
                    return $asset->nilai_buku;
                }),
                'total_depreciation' => $assets->sum(function($asset) {
                    return $asset->harga_perolehan - $asset->nilai_buku;
                })
            ];
            
            return response()->json(['summary' => $summary]);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-asset-detail" data-id="'.$row->id.'">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning edit-asset" data-id="'.$row->id.'">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>';
            })
            ->editColumn('harga_perolehan', function($row) {
                return 'Rp ' . number_format($row->harga_perolehan, 0, ',', '.');
            })
            ->editColumn('nilai_buku', function($row) {
                return 'Rp ' . number_format($row->nilai_buku, 0, ',', '.');
            })
            ->editColumn('tanggal_pembelian', function($row) {
                return date('d/m/Y', strtotime($row->tanggal_pembelian));
            })
            ->editColumn('status', function($row) {
                $badges = [
                    'aktif' => 'bg-success',
                    'nonaktif' => 'bg-warning',
                    'terjual' => 'bg-info',
                    'hilang' => 'bg-danger'
                ];
                return '<span class="badge '.$badges[$row->status].'">'.ucfirst($row->status).'</span>';
            })
            ->addColumn('akumulasi_susut', function($row) {
                $total = $row->depreciations->sum('nilai_penyusutan');
                return 'Rp ' . number_format($total, 0, ',', '.');
            })
            ->addColumn('calculate_monthly_depreciation', function($row) {
                // Hitung penyusutan per bulan
                if ($row->metode_penyusutan === 'garis_lurus') {
                    return ($row->harga_perolehan - $row->nilai_residu) / $row->umur_ekonomis;
                } 
                elseif ($row->metode_penyusutan === 'saldo_menurun') {
                    $ratePerBulan = ($row->persentase_susut ?? 20) / 100 / 12;
                    return $row->nilai_buku * $ratePerBulan;
                }
                return 0;
            })
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Generate penyusutan bulan berjalan
    public function generateMonthlyDepreciation(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'periode' => 'required|date_format:Y-m'
            ]);

            $periode = $request->periode . '-01';
            $projectId = session('active_project_id');

            // Ambil semua aset aktif
            $assets = Asset::where('idproject', $projectId)
                ->where('status', 'aktif')
                ->where('tanggal_mulai_susut', '<=', $periode)
                ->get();

            $count = 0;
            foreach ($assets as $asset) {
                // Cek apakah sudah ada penyusutan untuk periode ini
                $existing = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('periode', $periode)
                    ->first();

                if (!$existing) {
                    // Cek penyusutan terakhir
                    $lastDepreciation = AssetDepreciation::where('asset_id', $asset->id)
                        ->orderBy('periode', 'desc')
                        ->first();

                    $bulanKe = $lastDepreciation ? $lastDepreciation->bulan_ke + 1 : 1;
                    
                    // Hentikan jika sudah melebihi umur ekonomis
                    if ($bulanKe > $asset->umur_ekonomis) {
                        continue;
                    }

                    $nilaiPenyusutan = $asset->calculateMonthlyDepreciation();
                    $akumulasiSebelum = $lastDepreciation ? $lastDepreciation->akumulasi_penyusutan : 0;
                    $akumulasiSekarang = $akumulasiSebelum + $nilaiPenyusutan;
                    
                    // Nilai buku tidak boleh kurang dari nilai residu
                    $nilaiBuku = $asset->harga_perolehan - $akumulasiSekarang;
                    if ($nilaiBuku < $asset->nilai_residu) {
                        $nilaiPenyusutan = $asset->harga_perolehan - $asset->nilai_residu - $akumulasiSebelum;
                        $akumulasiSekarang = $akumulasiSebelum + $nilaiPenyusutan;
                        $nilaiBuku = $asset->nilai_residu;
                    }

                    AssetDepreciation::create([
                        'asset_id' => $asset->id,
                        'periode' => $periode,
                        'bulan_ke' => $bulanKe,
                        'nilai_penyusutan' => $nilaiPenyusutan,
                        'akumulasi_penyusutan' => $akumulasiSekarang,
                        'nilai_buku' => $nilaiBuku,
                        'status' => 'terbentuk',
                        'keterangan' => 'Penyusutan bulanan'
                    ]);

                    $count++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil generate {$count} penyusutan aset untuk periode {$request->periode}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAssets(Request $request)
    {
        try {
            $query = Asset::with(['project', 'kodeTransaksi'])
                ->where('idproject', session('active_project_id'));

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->filled('metode')) {
                $query->where('metode_penyusutan', $request->metode);
            }
            
            if ($request->filled('date_from')) {
                $query->where('tanggal_pembelian', '>=', $request->date_from);
            }
            
            if ($request->filled('date_to')) {
                $query->where('tanggal_pembelian', '<=', $request->date_to);
            }

            $assets = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="assets_' . date('Ymd_His') . '.csv"',
            ];

            $callback = function() use ($assets) {
                $file = fopen('php://output', 'w');
                fputcsv($file, [
                    'Kode Asset',
                    'Nama Asset', 
                    'Tanggal Pembelian',
                    'Harga Perolehan',
                    'Nilai Residu',
                    'Umur Ekonomis (bulan)',
                    'Metode Penyusutan',
                    'Persentase Susut (%)',
                    'Nilai Buku',
                    'Akumulasi Penyusutan',
                    'Status',
                    'Lokasi',
                    'PIC',
                    'Keterangan'
                ]);

                foreach ($assets as $asset) {
                    fputcsv($file, [
                        $asset->kode_aset,
                        $asset->nama_aset,
                        $asset->tanggal_pembelian,
                        $asset->harga_perolehan,
                        $asset->nilai_residu,
                        $asset->umur_ekonomis,
                        $asset->metode_penyusutan,
                        $asset->persentase_susut,
                        $asset->nilai_buku,
                        $asset->harga_perolehan - $asset->nilai_buku,
                        $asset->status,
                        $asset->lokasi,
                        $asset->pic,
                        $asset->keterangan
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}