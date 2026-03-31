<?php

namespace App\Http\Controllers;

use App\Exports\AssetExport;
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
use Maatwebsite\Excel\Facades\Excel;
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
                
                'paymen_method' => 'required|in:cash,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.nominal' => 'required|numeric|min:0',
                'transactions.*.jml' => 'required|numeric|min:1',
                'bukti_nota' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5000',
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

    private function processCashReceipt($nota, $idrek, $jumlah, $tanggal)
    {
        $rekening = Rekening::find($idrek);
        if (!$rekening) {
            throw new \Exception("Rekening tidak ditemukan");
        }

        $saldoAwal = $rekening->saldo;
        $rekening->saldo += $jumlah;
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
            'cashflow' => 'in',
            'nominal' => $jumlah,
            'saldo_awal' => $saldoAwal,
            'saldo_akhir' => $rekening->saldo,
            'keterangan' => "Penerimaan asset {$nota->nota_no}"
        ]);
    }

    private function createAssetIncomeNota(array $data)
    {
        $projectId = session('active_project_id');
        $project = Project::find($projectId);

        if (!$project) {
            throw new \Exception('Project tidak ditemukan');
        }

        $user = auth()->user();
        $nip = $user->nip ?? $user->id;
        $namauser = $user->name;
        $notaNo = $this->generateOperationNotaNumber($data['prefix'] ?? 'AST', $projectId);

        $nota = Nota::create([
            'nota_no' => $notaNo,
            'namatransaksi' => $data['namatransaksi'],
            'idproject' => $project->id,
            'idcompany' => $project->idcompany ?? session('active_project_company_id'),
            'idretail' => $project->idretail,
            'idrek' => $data['idrek'],
            'tanggal' => $data['tanggal'],
            'cashflow' => 'in',
            'paymen_method' => $data['paymen_method'],
            'tgl_tempo' => $data['tgl_tempo'] ?? null,
            'subtotal' => $data['nominal'],
            'ppn' => 0,
            'diskon' => 0,
            'total' => $data['nominal'],
            'status' => $data['paymen_method'] === 'cash' ? 'paid' : 'open',
            'nip' => $nip,
            'namauser' => $namauser,
            'type' => $data['prefix'] === 'ASW' ? 'asset_sewa' : 'asset_penghapusan',
        ]);

        NotaTransaction::create([
            'idnota' => $nota->id,
            'idkodetransaksi' => $data['idkodetransaksi'],
            'description' => $data['description'],
            'nominal' => $data['nominal'],
            'jml' => 1,
            'total' => $data['nominal'],
        ]);

        if ($data['paymen_method'] === 'cash') {
            $this->processCashReceipt($nota, $data['idrek'], $data['nominal'], $data['tanggal']);
        }

        return $nota;
    }

    private function generateOperationNotaNumber($prefix, $projectId)
    {
        $yearMonth = date('Ym');
        $lastNota = Nota::where('idproject', $projectId)
            ->where('nota_no', 'like', $prefix . '-%')
            ->orderBy('nota_no', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastNota) {
            $parts = explode('-', $lastNota->nota_no);
            $lastNumber = intval(end($parts));
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $projectId, $yearMonth, $lastNumber + 1);
    }

    // Halaman daftar aset
    public function assetList()
    {
        $projectId = session('active_project_id');
        $rekenings = Rekening::forProject($projectId)->get();
        $kodeTransaksi = KodeTransaksi::orderBy('kodetransaksi')->get(['id', 'kodetransaksi', 'transaksi']);

        return view('transaksi.asset.list', compact('rekenings', 'kodeTransaksi'));
    }

    /**
     * Datatable untuk daftar aset
     */
    public function getAssetData(Request $request)
    {
        $query = $this->buildAssetListQuery($request);

        // If only summary requested
        if ($request->has('summary_only')) {
            $assets = $query->get();
            
            $totalNilaiBuku = 0;
            $totalDepresiasi = 0;
            
            foreach ($assets as $asset) {
                $nilaiBuku = $asset->nilai_buku;
                $totalNilaiBuku += $nilaiBuku;
                $totalDepresiasi += ($asset->harga_perolehan - $nilaiBuku);
            }
            
            $summary = [
                'total_assets' => $assets->count(),
                'total_value' => $assets->sum('harga_perolehan'),
                'total_book_value' => $totalNilaiBuku,
                'total_depreciation' => $totalDepresiasi
            ];
            
            return response()->json(['summary' => $summary]);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $canDispose = !in_array($row->status, ['terjual', 'rusak'], true);
                $canRent = !in_array($row->status, ['terjual', 'rusak'], true);

                $disposalBtn = $canDispose
                    ? '<button class="btn btn-sm btn-outline-danger dispose-asset" data-id="'.$row->id.'" data-name="'.e($row->nama_aset).'">
                        <i class="bi bi-box-arrow-down"></i>
                    </button>'
                    : '<button class="btn btn-sm btn-outline-danger" disabled>
                        <i class="bi bi-box-arrow-down"></i>
                    </button>';

                $rentBtn = $canRent
                    ? '<button class="btn btn-sm btn-outline-success rent-asset" data-id="'.$row->id.'" data-name="'.e($row->nama_aset).'">
                        <i class="bi bi-cash-coin"></i>
                    </button>'
                    : '<button class="btn btn-sm btn-outline-success" disabled>
                        <i class="bi bi-cash-coin"></i>
                    </button>';

                $deleteBtn = $canDelete ? 
                    '<button class="btn btn-sm btn-danger delete-asset" data-id="'.$row->id.'" data-name="'.$row->nama_aset.'"><i class="bi bi-trash"></i></button>' :
                    '<button class="btn btn-sm btn-danger" disabled><i class="bi bi-trash"></i></button>';
                
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-asset-detail" data-id="'.$row->id.'">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning edit-asset" data-id="'.$row->id.'">
                        <i class="bi bi-pencil"></i>
                    </button>
                    '.$disposalBtn.'
                    '.$rentBtn.'
                    '.$deleteBtn.'
                </div>';
            })
            ->editColumn('tanggal_pembelian', function($row) {
                return $row->tanggal_pembelian ? date('d/m/Y', strtotime($row->tanggal_pembelian)) : '-';
            })
            ->editColumn('harga_perolehan', function($row) {
                return floatval($row->harga_perolehan ?? 0);
            })
            ->editColumn('nilai_buku', function($row) {
                return floatval($row->nilai_buku ?? 0);
            })
            ->addColumn('akumulasi_susut', function($row) {
                $total = $row->depreciations->sum('nilai_penyusutan');
                return floatval($total ?? 0);
            })
            ->editColumn('status', function($row) {
                $badges = [
                    'aktif' => 'bg-success',
                    'nonaktif' => 'bg-warning',
                    'terjual' => 'bg-info',
                    'hilang' => 'bg-danger',
                    'rusak' => 'bg-danger',
                    'disewakan' => 'bg-primary'
                ];
                return '<span class="badge '.($badges[$row->status] ?? 'bg-secondary').'">'.ucfirst($row->status).'</span>';
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
            $assets = $this->buildAssetListQuery($request)->get();
            $filename = 'data_aset_' . now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new AssetExport($assets), $filename);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildAssetListQuery(Request $request)
    {
        $query = Asset::with([
                'project',
                'kodeTransaksi',
                'depreciations' => function($q) {
                    $q->where('status', 'terposting');
                }
            ])
            ->where('idproject', session('active_project_id'));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('metode')) {
            $query->where('metode_penyusutan', $request->metode);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal_pembelian', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('tanggal_pembelian', '<=', $request->date_to);
        }

        return $query;
    }

    /**
     * Get detail asset untuk view
     */
    public function getAssetDetail($id)
    {
        try {
            $asset = Asset::with([
                'project', 
                'kodeTransaksi',
                'depreciations' => function($q) {
                    $q->orderBy('periode', 'asc');
                }
            ])->findOrFail($id);

            // Hitung penyusutan per bulan dengan aman
            $monthlyDepreciation = 0;
            if ($asset->metode_penyusutan === 'garis_lurus') {
                $hargaPerolehan = floatval($asset->harga_perolehan ?? 0);
                $nilaiResidu = floatval($asset->nilai_residu ?? 0);
                $umurEkonomis = intval($asset->umur_ekonomis ?? 1);
                
                if ($umurEkonomis > 0) {
                    $monthlyDepreciation = ($hargaPerolehan - $nilaiResidu) / $umurEkonomis;
                }
            } 
            elseif ($asset->metode_penyusutan === 'saldo_menurun') {
                $ratePerBulan = (floatval($asset->persentase_susut ?? 20) / 100 / 12);
                $nilaiBuku = floatval($asset->nilai_buku);
                $monthlyDepreciation = $nilaiBuku * $ratePerBulan;
            }

            // Pastikan nilai_buku dihitung dengan benar
            $totalDepreciation = $asset->depreciations()
                ->where('status', 'terposting')
                ->sum('nilai_penyusutan');
            
            $nilaiBuku = floatval($asset->harga_perolehan ?? 0) - floatval($totalDepreciation ?? 0);

            return response()->json([
                'success' => true,
                'asset' => [
                    'id' => $asset->id,
                    'kode_aset' => $asset->kode_aset,
                    'nama_aset' => $asset->nama_aset,
                    'tanggal_pembelian' => $asset->tanggal_pembelian ? $asset->tanggal_pembelian->format('Y-m-d') : null,
                    'tanggal_mulai_susut' => $asset->tanggal_mulai_susut ? $asset->tanggal_mulai_susut->format('Y-m-d') : null,
                    'harga_perolehan' => floatval($asset->harga_perolehan ?? 0),
                    'nilai_residu' => floatval($asset->nilai_residu ?? 0),
                    'umur_ekonomis' => intval($asset->umur_ekonomis ?? 0),
                    'metode_penyusutan' => $asset->metode_penyusutan,
                    'persentase_susut' => floatval($asset->persentase_susut ?? 0),
                    'status' => $asset->status,
                    'lokasi' => $asset->lokasi,
                    'pic' => $asset->pic,
                    'keterangan' => $asset->keterangan,
                    'kode_transaksi' => $asset->kodeTransaksi ? $asset->kodeTransaksi->kodetransaksi : null,
                    'nilai_buku' => $nilaiBuku,
                    'depreciations' => $asset->depreciations->map(function($dep) {
                        return [
                            'periode' => $dep->periode ? $dep->periode->format('Y-m-d') : null,
                            'bulan_ke' => $dep->bulan_ke,
                            'nilai_penyusutan' => floatval($dep->nilai_penyusutan ?? 0),
                            'akumulasi_penyusutan' => floatval($dep->akumulasi_penyusutan ?? 0),
                            'nilai_buku' => floatval($dep->nilai_buku ?? 0),
                            'status' => $dep->status
                        ];
                    })
                ],
                'calculate_monthly_depreciation' => $monthlyDepreciation
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getAssetDetail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data asset: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editAsset($id)
    {
        try {
            $asset = Asset::with(['project', 'kodeTransaksi'])->findOrFail($id);
            
            // Format tanggal dengan benar untuk input date (Y-m-d)
            $formattedAsset = [
                'id' => $asset->id,
                'kode_aset' => $asset->kode_aset,
                'nama_aset' => $asset->nama_aset,
                'tanggal_mulai_susut' => $asset->tanggal_mulai_susut ? $asset->tanggal_mulai_susut->format('Y-m-d') : null,
                'umur_ekonomis' => $asset->umur_ekonomis,
                'nilai_residu' => $asset->nilai_residu,
                'metode_penyusutan' => $asset->metode_penyusutan,
                'persentase_susut' => $asset->persentase_susut,
                'status' => $asset->status,
                'lokasi' => $asset->lokasi,
                'pic' => $asset->pic,
                'keterangan' => $asset->keterangan,
            ];
            
            return response()->json([
                'success' => true,
                'asset' => $formattedAsset
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error editAsset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data asset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update asset
     */
    public function updateAsset(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nama_aset' => 'required|string|max:255',
                'tanggal_mulai_susut' => 'required|date',
                'umur_ekonomis' => 'required|integer|min:1',
                'nilai_residu' => 'nullable|numeric|min:0',
                'metode_penyusutan' => 'required|in:garis_lurus,saldo_menurun',
                'persentase_susut' => 'nullable|numeric|min:0|max:100',
                'status' => 'required|in:aktif,nonaktif,terjual,hilang,rusak,disewakan',
                'lokasi' => 'nullable|string|max:100',
                'pic' => 'nullable|string|max:100',
                'keterangan' => 'nullable|string',
            ]);

            $asset = Asset::findOrFail($id);
            
            // Cek apakah ada perubahan pada data yang mempengaruhi penyusutan
            $affectsDepreciation = (
                $asset->umur_ekonomis != $request->umur_ekonomis ||
                $asset->nilai_residu != $request->nilai_residu ||
                $asset->metode_penyusutan != $request->metode_penyusutan ||
                $asset->persentase_susut != $request->persentase_susut ||
                $asset->tanggal_mulai_susut != $request->tanggal_mulai_susut
            );

            // Update asset
            $asset->update([
                'nama_aset' => $request->nama_aset,
                'tanggal_mulai_susut' => $request->tanggal_mulai_susut,
                'umur_ekonomis' => $request->umur_ekonomis,
                'nilai_residu' => $request->nilai_residu ?? 0,
                'metode_penyusutan' => $request->metode_penyusutan,
                'persentase_susut' => $request->persentase_susut,
                'status' => $request->status,
                'lokasi' => $request->lokasi,
                'pic' => $request->pic,
                'keterangan' => $request->keterangan,
            ]);

            // Jika ada perubahan yang mempengaruhi penyusutan, update schedule penyusutan
            if ($affectsDepreciation) {
                $this->recalculateDepreciation($asset);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function disposeAsset(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'tipe_penghapusan' => 'required|in:rusak,terjual,lainnya',
                'tanggal' => 'required|date',
                'keterangan' => 'nullable|string',
                'pihak_terkait' => 'nullable|string|max:255',
                'nilai' => 'nullable|numeric|min:0',
                'idrek' => 'nullable|exists:rekening,idrek',
                'paymen_method' => 'nullable|in:cash,tempo',
                'tgl_tempo' => 'nullable|date',
                'idkodetransaksi' => 'nullable|exists:kodetransaksi,id',
            ]);

            $asset = Asset::findOrFail($id);

            if (in_array($asset->status, ['terjual', 'rusak'], true)) {
                throw new \Exception('Asset ini sudah diproses penghapusannya.');
            }

            $nota = null;
            $nilai = (float) ($request->nilai ?? 0);
            if ($request->tipe_penghapusan === 'terjual') {
                if (!$request->idrek || !$request->paymen_method || !$request->idkodetransaksi || $nilai <= 0) {
                    throw new \Exception('Untuk penghapusan terjual, rekening, payment method, kode transaksi, dan nilai jual wajib diisi.');
                }

                $nota = $this->createAssetIncomeNota([
                    'prefix' => 'ASJ',
                    'tanggal' => $request->tanggal,
                    'namatransaksi' => 'Penjualan Asset - ' . $asset->nama_aset,
                    'description' => 'Penjualan asset ' . $asset->nama_aset,
                    'idrek' => $request->idrek,
                    'paymen_method' => $request->paymen_method,
                    'tgl_tempo' => $request->paymen_method === 'tempo' ? $request->tgl_tempo : null,
                    'idkodetransaksi' => $request->idkodetransaksi,
                    'nominal' => $nilai,
                ]);
            }

            AssetMutation::create([
                'asset_id' => $asset->id,
                'tanggal' => $request->tanggal,
                'jenis' => 'penghapusan',
                'subjenis' => $request->tipe_penghapusan,
                'nilai' => $nilai,
                'pihak_terkait' => $request->pihak_terkait,
                'keterangan' => $request->keterangan,
                'idnota' => $nota?->id,
            ]);

            $asset->status = match ($request->tipe_penghapusan) {
                'terjual' => 'terjual',
                'rusak' => 'rusak',
                default => 'nonaktif',
            };
            $asset->keterangan = trim(collect([
                $asset->keterangan,
                'Penghapusan aset (' . $request->tipe_penghapusan . ') tanggal ' . $request->tanggal . ($request->keterangan ? ': ' . $request->keterangan : '')
            ])->filter()->implode(' | '));
            $asset->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penghapusan asset berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rentAsset(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'tanggal' => 'required|date',
                'tanggal_mulai' => 'required|date',
                'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
                'penyewa' => 'required|string|max:255',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'tgl_tempo' => 'nullable|date',
                'idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'nilai_sewa' => 'required|numeric|min:0.01',
                'keterangan' => 'nullable|string',
            ]);

            $asset = Asset::findOrFail($id);
            if (in_array($asset->status, ['terjual', 'rusak'], true)) {
                throw new \Exception('Asset dengan status ini tidak bisa disewakan.');
            }

            $nota = $this->createAssetIncomeNota([
                'prefix' => 'ASW',
                'tanggal' => $request->tanggal,
                'namatransaksi' => 'Sewa Asset - ' . $asset->nama_aset,
                'description' => 'Sewa asset ' . $asset->nama_aset . ' - ' . $request->penyewa,
                'idrek' => $request->idrek,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method === 'tempo' ? $request->tgl_tempo : null,
                'idkodetransaksi' => $request->idkodetransaksi,
                'nominal' => $request->nilai_sewa,
            ]);

            AssetMutation::create([
                'asset_id' => $asset->id,
                'tanggal' => $request->tanggal,
                'jenis' => 'sewa',
                'nilai' => $request->nilai_sewa,
                'pihak_terkait' => $request->penyewa,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'keterangan' => $request->keterangan,
                'idnota' => $nota->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi sewa asset berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete asset
     */
    public function destroyAsset($id)
    {
        DB::beginTransaction();
        try {
            $asset = Asset::findOrFail($id);
            
            // Cek apakah user memiliki hak akses
            $user = auth()->user();
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menghapus asset'
                ], 403);
            }

            // Cek apakah asset sudah memiliki transaksi penyusutan yang terposting
            $hasPostedDepreciation = AssetDepreciation::where('asset_id', $asset->id)
                ->where('status', 'terposting')
                ->exists();

            if ($hasPostedDepreciation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus asset yang sudah memiliki penyusutan terposting'
                ]);
            }

            // Hapus semua penyusutan terkait
            AssetDepreciation::where('asset_id', $asset->id)->delete();
            
            // Hapus asset
            $asset->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Asset berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate depreciation schedule
     */
    private function recalculateDepreciation(Asset $asset)
    {
        // Hapus semua schedule penyusutan yang terbentuk
        AssetDepreciation::where('asset_id', $asset->id)
            ->where('status', 'terbentuk')
            ->delete();

        // Buat ulang schedule penyusutan
        $this->generateFirstDepreciation($asset);
    }

    /**
     * Edit transaksi
     */
    public function editTransaksi($id)
    {
        try {
            $nota = Nota::with(['transactions.kodeTransaksi', 'vendor', 'project'])
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'nota' => $nota
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update transaksi
     */
    public function updateTransaksi(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                
                'paymen_method' => 'required|in:cash,tempo',
                'ppn' => 'nullable|numeric|min:0',
                'diskon' => 'nullable|numeric|min:0',
            ]);

            $nota = Nota::findOrFail($id);
            
            // Cek apakah sudah ada aset yang digenerate
            $hasAsset = Asset::where('idnota', $id)->exists();
            if ($hasAsset) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengubah transaksi yang sudah memiliki aset'
                ], 400);
            }

            // Update nota
            $nota->update([
                'namatransaksi' => $request->namatransaksi,
                'tanggal' => $request->tanggal,
                'idrek' => $request->idrek,
                'vendor_id' => $request->vendor_id,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'ppn' => $request->ppn ?? 0,
                'diskon' => $request->diskon ?? 0,
                'total' => $nota->subtotal + ($request->ppn ?? 0) - ($request->diskon ?? 0),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete transaksi
     */
    public function destroyTransaksi($id)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk menghapus transaksi'
                ], 403);
            }

            $nota = Nota::findOrFail($id);
            
            // Cek apakah sudah ada aset
            $hasAsset = Asset::where('idnota', $id)->exists();
            if ($hasAsset) {
                // Hapus semua aset terkait beserta penyusutannya
                $assets = Asset::where('idnota', $id)->get();
                foreach ($assets as $asset) {
                    AssetDepreciation::where('asset_id', $asset->id)->delete();
                    $asset->delete();
                }
            }

            // Hapus transaksi details
            NotaTransaction::where('idnota', $id)->delete();
            
            // Hapus pembayaran jika ada
            NotaPayment::where('idnota', $id)->delete();
            
            // Hapus nota
            $nota->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateNotaNumber()
    {
        try {
            $projectId = session('active_project_id');
            $yearMonth = date('Ym');
            
            // Cari nomor invoice terakhir dengan format yang lebih fleksibel
            $lastNota = Nota::where('idproject', $projectId)
                ->whereYear('created_at', date('Y'))
                ->whereMonth('created_at', date('m'))
                ->where('nota_no', 'like', 'AST-%')
                ->orderBy('nota_no', 'desc')
                ->first();
            
            if ($lastNota) {
                // Extract nomor urut dari nota_no
                // Format: AST-{project}-{tahunbulan}-{nomor}
                $parts = explode('-', $lastNota->nota_no);
                $lastNumber = intval(end($parts));
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
            
            $notaNo = "AST-{$projectId}-{$yearMonth}-{$newNumber}";
            
            return response()->json([
                'success' => true,
                'nota_no' => $notaNo
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error generate nota: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate nomor nota: ' . $e->getMessage()
            ], 500);
        }
    }
}
