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
use App\Models\Barang;
use App\Models\StockProject;
use App\Models\StockHistory;
use App\Models\TransUpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class TokoController extends Controller
{
    // Halaman transaksi pembelian (in)
    public function pembelian()
    {
        return view('transaksi.toko.pembelian');
    }

    // Halaman transaksi penjualan (out)
    public function penjualan()
    {
        return view('transaksi.toko.penjualan');
    }

    // Halaman stock management
    public function stock()
    {
        return view('transaksi.toko.stock');
    }

    // Datatable untuk transaksi pembelian
    public function getDataPembelian()
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor'
            ])
            ->where('cashflow', 'out')
            ->where('type', 'toko')
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
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Datatable untuk transaksi penjualan
    public function getDataPenjualan()
    {
        $query = Nota::with([
                'project:id,namaproject',
                'vendor:id,namavendor'
            ])
            ->where('cashflow', 'in')
            ->where('type', 'toko')
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
            ->rawColumns(['action', 'status'])
            ->toJson();
    }

    // Datatable untuk stock barang
    public function getDataStock()
    {
        $projectId = session('active_project_id');
        
        $query = Barang::query()
    ->select(
        'barang.idbarang',
        'barang.nama_barang',
        'barang.harga_beli',
        'barang.harga_jual',
        'barang.deskripsi',

        DB::raw('COALESCE(MAX(sp.stock), 0) as stock_project'),
        DB::raw('COALESCE(SUM(CASE WHEN sh.tipe = "masuk" THEN sh.qty ELSE 0 END), 0) as total_masuk'),
        DB::raw('COALESCE(SUM(CASE WHEN sh.tipe = "keluar" THEN sh.qty ELSE 0 END), 0) as total_keluar')
    )

    ->leftJoin('stock_project as sp', function ($join) use ($projectId) {
        $join->on('sp.barang_id', '=', 'barang.idbarang')
             ->where('sp.project_id', $projectId)
             ->whereNull('sp.deleted_at');
    })

    ->leftJoin('stock_history as sh', function ($join) use ($projectId) {
        $join->on('sh.barang_id', '=', 'barang.idbarang')
             ->where('sh.project_id', $projectId);
    })

    ->whereNull('barang.deleted_at')

    ->groupBy(
        'barang.idbarang',
        'barang.nama_barang',
        'barang.harga_beli',
        'barang.harga_jual',
        'barang.deskripsi'
    );


        return DataTables::eloquent($query)
            ->addIndexColumn() // Ini membuat DT_RowIndex
            ->addColumn('action', function($row) {
                return '<div class="btn-group">
                    <button class="btn btn-sm btn-info view-barang-btn" data-id="'.$row->idbarang.'"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-warning edit-barang-btn" data-id="'.$row->idbarang.'"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-primary adjust-stock-btn" data-id="'.$row->idbarang.'"><i class="bi bi-box-arrow-in-down"></i></button>
                    <button class="btn btn-sm btn-secondary view-history-btn" data-id="'.$row->idbarang.'" data-name="'.$row->nama_barang.'"><i class="bi bi-clock-history"></i></button>
                </div>';
            })
            ->editColumn('harga_beli', function($row) {
                return 'Rp ' . number_format($row->harga_beli, 0, ',', '.');
            })
            ->editColumn('harga_jual', function($row) {
                return 'Rp ' . number_format($row->harga_jual, 0, ',', '.');
            })
            ->editColumn('stock_project', function($row) {
                $stock = $row->stock_project ?? 0;
                $badge = $stock > 10 ? 'bg-success' : ($stock > 0 ? 'bg-warning' : 'bg-danger');
                return '<span class="badge '.$badge.'">'.$stock.'</span>';
            })
            ->editColumn('total_masuk', function($row) {
                return '<span class="text-success">' . number_format($row->total_masuk, 0, ',', '.') . '</span>';
            })
            ->editColumn('total_keluar', function($row) {
                return '<span class="text-danger">' . number_format($row->total_keluar, 0, ',', '.') . '</span>';
            })
            ->filter(function($query) {
                $search = request('search.value');
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
                    });
                }
            })
            // Tambahkan order column untuk mengatasi error
            ->order(function($query) {
                // Default order
                if (!request()->has('order')) {
                    $query->orderBy('barang.created_at', 'desc');
                }
            })
            // Tentukan kolom yang bisa di-order
            ->orderColumn('DT_RowIndex', function($query, $order) {
                // Kolom DT_RowIndex tidak perlu order di database
                return $query;
            })
            ->orderColumn('nama_barang', 'nama_barang $1')
            ->orderColumn('harga_beli', 'harga_beli $1')
            ->orderColumn('harga_jual', 'harga_jual $1')
            ->orderColumn('stock_project', 'stock_project $1')
            ->rawColumns(['action', 'stock_project', 'total_masuk', 'total_keluar'])
            ->toJson();
    }

    // Simpan transaksi pembelian
    public function storePembelian(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'transactions' => 'required|array|min:1',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'vendor_id' => 'nullable|exists:vendors,id',
            ]);

            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;

            // Ambil project berdasarkan session
            $projectId = session('active_project_id');
            $project = Project::find($projectId);

            if (!$project) {
                throw new \Exception("Project dengan ID {$projectId} tidak ditemukan");
            }

            $idcompany = $project->idcompany ?? session('active_project_company_id');
            $idretail = $project->idretail;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_toko_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            // Hitung total
            $subtotal = 0;
            foreach ($request->transactions as $transaction) {
                $subtotal += $transaction['qty'] * $transaction['harga_beli'];
            }

            $total = $subtotal;

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
                'cashflow' => 'in',
                'type' => 'toko',
                'paymen_method' => $request->paymen_method ?? 'cash',
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => 0,
                'diskon' => 0,
                'total' => $total,
                'status' => 'paid',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi dan update stock
                foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['qty'] * $transaction['harga_beli'];
                
                // Cari atau buat barang
                $barang = null;
                if (isset($transaction['idbarang']) && $transaction['idbarang']) {
                    $barang = Barang::find($transaction['idbarang']);
                    if ($barang) {
                        // Update harga jika berbeda
                        if ($barang->harga_beli != $transaction['harga_beli']) {
                            $barang->update(['harga_beli' => $transaction['harga_beli']]);
                        }
                        if ($barang->harga_jual != $transaction['harga_jual']) {
                            $barang->update(['harga_jual' => $transaction['harga_jual']]);
                        }
                    }
                } else if (isset($transaction['nama_barang']) && $transaction['nama_barang']) {
                    // Buat barang baru
                    $barang = Barang::create([
                        'nama_barang' => $transaction['nama_barang'],
                        'harga_beli' => $transaction['harga_beli'],
                        'harga_jual' => $transaction['harga_jual'] ?? $transaction['harga_beli'] * 1.3,
                        'deskripsi' => $transaction['deskripsi'] ?? null,
                    ]);
                }
                
                if (!$barang) {
                    throw new \Exception("Barang tidak valid");
                }

                // Simpan transaksi
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => $barang->idbarang,
                    'idkodetransaksi' => "77",
                    'description' => $barang->nama_barang,
                    'nominal' => $transaction['harga_beli'],
                    'jml' => $transaction['qty'],
                    'total' => $itemTotal,
                ]);

                // Update stock toko (project utama)
                $stockProject = StockProject::firstOrCreate(
                    [
                        'barang_id' => $barang->idbarang,
                        'project_id' => $projectId
                    ],
                    ['stock' => 0]
                );

                $stockSebelum = $stockProject->stock;
                $stockProject->stock += $transaction['qty'];
                $stockProject->save();

                // Catat history stock
                StockHistory::create([
                    'barang_id' => $barang->idbarang,
                    'project_id' => $projectId,
                    'tipe' => 'masuk',
                    'qty' => $transaction['qty'],
                    'qty_sebelum' => $stockSebelum,
                    'qty_sesudah' => $stockProject->stock,
                    'keterangan' => "Pembelian - Nota: {$nota->nota_no}",
                    'idnota' => $nota->id,
                    'created_by' => $user->id
                ]);
            }

            // Proses pembayaran
            $this->processPayment($nota, $request->idrek, $total, $request->tanggal, 'in');

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Pembelian barang - No: {$nota->nota_no}, Total: Rp " . number_format($total, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembelian berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Pembelian Error:', [
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

    // Simpan transaksi penjualan
    public function storePenjualan(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'transactions' => 'required|array|min:1',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'jenis_penjualan' => 'required|in:toko,project',

            ]);

            // Ambil user yang login
            $user = auth()->user();
            $nip = $user->nip ?? $user->id;
            $namauser = $user->name;

            // Ambil project toko (utama)
            $projectTokoId = session('active_project_id');
            $projectToko = Project::find($projectTokoId);

            if (!$projectToko) {
                throw new \Exception("Project toko dengan ID {$projectTokoId} tidak ditemukan");
            }

            $idcompany = $projectToko->idcompany ?? session('active_project_company_id');
            $idretail = $projectToko->idretail;

            // Handle upload bukti nota
            $buktiNotaPath = null;
            if ($request->hasFile('bukti_nota')) {
                $file = $request->file('bukti_nota');
                $filename = 'nota_penjualan_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            // Hitung total
            $subtotal = 0;
            foreach ($request->transactions as $transaction) {
                $subtotal += $transaction['qty'] * $transaction['harga_jual'];
            }

            $total = $subtotal;

            // Data untuk nota header
            $notaData = [
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'idproject' => $projectToko->id,
                'idcompany' => $idcompany,
                'idretail' => $idretail,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => 'out',
                'type' => 'toko',
                'paymen_method' => $request->paymen_method ?? 'cash',
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => 0,
                'diskon' => 0,
                'total' => $total,
                'status' => 'paid',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi dan update stock
            foreach ($request->transactions as $transaction) {
                $barang = Barang::find($transaction['idbarang']);
                if (!$barang) {
                    throw new \Exception("Barang dengan ID {$transaction['idbarang']} tidak ditemukan");
                }

                // Cek stock cukup di toko
                $stockToko = StockProject::where('barang_id', $barang->idbarang)
                    ->where('project_id', $projectTokoId)
                    ->first();
                
                if (!$stockToko || $stockToko->stock < $transaction['qty']) {
                    throw new \Exception("Stock barang {$barang->nama_barang} tidak cukup. Stock tersedia: " . ($stockToko->stock ?? 0));
                }

                $itemTotal = $transaction['qty'] * $transaction['harga_jual'];
                
                // Simpan transaksi
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => $barang->idbarang,
                    'idkodetransaksi' => "78",
                    'description' => $barang->nama_barang,
                    'nominal' => $transaction['harga_jual'],
                    'jml' => $transaction['qty'],
                    'total' => $itemTotal,
                ]);

                // Update harga jual jika berbeda
                if ($barang->harga_jual != $transaction['harga_jual']) {
                    $barang->update(['harga_jual' => $transaction['harga_jual']]);
                }

                // Kurangi stock toko
                $stockSebelum = $stockToko->stock;
                $stockToko->stock -= $transaction['qty'];
                $stockToko->save();

                // Catat history stock toko (keluar)
                StockHistory::create([
                    'barang_id' => $barang->idbarang,
                    'project_id' => $projectTokoId,
                    'tipe' => 'keluar',
                    'qty' => $transaction['qty'],
                    'qty_sebelum' => $stockSebelum,
                    'qty_sesudah' => $stockToko->stock,
                    'keterangan' => "Penjualan - Nota: {$nota->nota_no}",
                    'idnota' => $nota->id,
                    'created_by' => $user->id
                ]);

                // Jika penjualan ke project, tambah stock project tujuan
                if ($request->jenis_penjualan == 'project' && $request->project_tujuan_id) {
                    $stockProject = StockProject::firstOrCreate(
                        [
                            'barang_id' => $barang->idbarang,
                            'project_id' => $request->project_tujuan_id
                        ],
                        ['stock' => 0]
                    );

                    $stockSebelumProject = $stockProject->stock;
                    $stockProject->stock += $transaction['qty'];
                    $stockProject->save();

                    // Catat history stock project (masuk)
                    StockHistory::create([
                        'barang_id' => $barang->idbarang,
                        'project_id' => $request->project_tujuan_id,
                        'tipe' => 'masuk',
                        'qty' => $transaction['qty'],
                        'qty_sebelum' => $stockSebelumProject,
                        'qty_sesudah' => $stockProject->stock,
                        'keterangan' => "Transfer dari toko - Nota: {$nota->nota_no}",
                        'idnota' => $nota->id,
                        'created_by' => $user->id
                    ]);
                }
            }

            // Proses pembayaran
            $this->processPayment($nota, $request->idrek, $total, $request->tanggal, 'out');

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Penjualan {$request->jenis_penjualan} - No: {$nota->nota_no}, Total: Rp " . number_format($total, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penjualan berhasil disimpan',
                'nota_id' => $nota->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Penjualan Error:', [
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

    // Get data barang untuk autocomplete
    public function getBarang(Request $request)
    {
        $search = $request->get('search');
        
        $barang = Barang::where('nama_barang', 'like', "%{$search}%")
            ->orWhere('deskripsi', 'like', "%{$search}%")
            ->limit(10)
            ->get();
            
        $data = [];
        foreach ($barang as $item) {
            $stock = StockProject::where('barang_id', $item->idbarang)
                ->where('project_id', session('active_project_id'))
                ->first();
                
            $data[] = [
                'id' => $item->idbarang,
                'text' => $item->nama_barang . 
                    ' | Harga Beli: Rp ' . number_format($item->harga_beli, 0, ',', '.') .
                    ' | Harga Jual: Rp ' . number_format($item->harga_jual, 0, ',', '.') .
                    ' | Stock: ' . ($stock->stock ?? 0)
            ];
        }
        
        return response()->json($data);
    }

    // Get detail barang
    public function getDetailBarang($id)
    {
        try {
            $barang = Barang::find($id);
            if (!$barang) {
                return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan']);
            }
            
            $projectId = session('active_project_id');
            
            // Ambil stock dari tabel stock_project
            $stockProject = StockProject::where('barang_id', $id)
                ->where('project_id', $projectId)
                ->first();
                
            $stock = $stockProject ? $stockProject->stock : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'barang' => $barang,
                    'stock' => $stock
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Adjust stock manual
    public function adjustStock(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'barang_id' => 'required|exists:barang,idbarang',
                'tipe' => 'required|in:masuk,keluar',
                'qty' => 'required|integer|min:1',
                'keterangan' => 'required|string|max:255'
            ]);

            $user = auth()->user();
            $projectId = session('active_project_id');
            $barang = Barang::find($request->barang_id);

            if (!$barang) {
                throw new \Exception("Barang tidak ditemukan");
            }

            $stockProject = StockProject::firstOrCreate(
                [
                    'barang_id' => $barang->idbarang,
                    'project_id' => $projectId
                ],
                ['stock' => 0]
            );

            $stockSebelum = $stockProject->stock;
            
            if ($request->tipe == 'masuk') {
                $stockProject->stock += $request->qty;
            } else {
                if ($stockProject->stock < $request->qty) {
                    throw new \Exception("Stock tidak cukup. Stock tersedia: {$stockProject->stock}");
                }
                $stockProject->stock -= $request->qty;
            }
            
            $stockProject->save();

            // Catat history
            StockHistory::create([
                'barang_id' => $barang->idbarang,
                'project_id' => $projectId,
                'tipe' => $request->tipe,
                'qty' => $request->qty,
                'qty_sebelum' => $stockSebelum,
                'qty_sesudah' => $stockProject->stock,
                'keterangan' => $request->keterangan,
                'created_by' => $user->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock berhasil disesuaikan',
                'stock_baru' => $stockProject->stock
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get stock history
    public function getStockHistory($barangId)
    {
        $projectId = session('active_project_id');
        
        $history = StockHistory::with(['user:id,name'])
            ->where('barang_id', $barangId)
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    // Update barang
    public function updateBarang(Request $request, $id)
    {
        try {
            $request->validate([
                'nama_barang' => 'required|string|max:150',
                'harga_beli' => 'required|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string|max:255'
            ]);

            $barang = Barang::find($id);
            if (!$barang) {
                return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan']);
            }

            $barang->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Show detail nota
    public function show($id)
    {
        try {
            $nota = Nota::with([
                'project',
                'vendor', 
                'rekening',
                'transactions' => function($q) {
                    $q->with('barang')
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
                'message' => 'Nota tidak ditemukan'
            ], 404);
        }
    }

    // Get data untuk form edit
    public function edit($id)
    {
        try {
            $nota = Nota::with([
                'vendor',
                'transactions' => function($q) {
                    $q->with('barang')
                      ->orderBy('id');
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $nota
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nota tidak ditemukan: ' . $e->getMessage()
            ], 404);
        }
    }

    // Delete transaksi
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $nota = Nota::with(['transactions'])->findOrFail($id);
            $user = auth()->user();
            
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus transaksi'
                ], 403);
            }

            // Rollback stock untuk pembelian (keluar) atau penjualan (masuk)
            foreach ($nota->transactions as $transaction) {
                if ($transaction->idbarang) {
                    $stockProject = StockProject::where('barang_id', $transaction->idbarang)
                        ->where('project_id', $nota->idproject)
                        ->first();
                    
                    if ($stockProject) {
                        $stockSebelum = $stockProject->stock;
                        
                        if ($nota->cashflow == 'in') {
                            // Pembelian: kurangi stock
                            $stockProject->stock -= $transaction->jml;
                        } else {
                            // Penjualan: tambah stock
                            $stockProject->stock += $transaction->jml;
                        }
                        
                        $stockProject->save();
                        
                        // Catat rollback history
                        StockHistory::create([
                            'barang_id' => $transaction->idbarang,
                            'project_id' => $nota->idproject,
                            'tipe' => 'adjust',
                            'qty' => $transaction->jml,
                            'qty_sebelum' => $stockSebelum,
                            'qty_sesudah' => $stockProject->stock,
                            'keterangan' => "Rollback - Hapus nota: {$nota->nota_no}",
                            'idnota' => $nota->id,
                            'created_by' => $user->id
                        ]);
                    }
                }
            }

            // Hapus file bukti nota jika ada
            if ($nota->bukti_nota) {
                Storage::disk('public')->delete($nota->bukti_nota);
            }

            // Hapus data terkait
            NotaTransaction::where('idnota', $nota->id)->delete();
            NotaPayment::where('idnota', $nota->id)->delete();
            Cashflow::where('idnota', $nota->id)->delete();
            StockHistory::where('idnota', $nota->id)->delete();

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Transaksi dihapus - No: {$nota->nota_no}, Total: Rp " . number_format($nota->total, 0, ',', '.'));

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

    // Helper methods
    private function createUpdateLog($notaId, $notaNo, $logMessage)
    {
        return TransUpdateLog::create([
            'idnota' => $notaId,
            'nota_no' => $notaNo,
            'update_log' => $logMessage
        ]);
    }

    private function processPayment($nota, $idrek, $jumlah, $tanggal, $cashflow)
    {
        try {
            $rekening = Rekening::find($idrek);
            if (!$rekening) {
                throw new \Exception("Rekening dengan ID {$idrek} tidak ditemukan");
            }

            $saldoAwal = $rekening->saldo;
            
            if ($cashflow == 'out') {
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
                'cashflow' => $cashflow,
                'nominal' => $jumlah,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran nota {$nota->nota_no} - {$cashflow}"
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Payment processing error:', [
                'message' => $e->getMessage(),
                'nota_id' => $nota->id
            ]);
            throw $e;
        }
    }

    // Create barang baru
    public function createBarang(Request $request)
    {
        try {
            $request->validate([
                'nama_barang' => 'required|string|max:150',
                'harga_beli' => 'required|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'deskripsi' => 'nullable|string|max:255'
            ]);

            $barang = Barang::create([
                'nama_barang' => $request->nama_barang,
                'harga_beli' => $request->harga_beli,
                'harga_jual' => $request->harga_jual,
                'deskripsi' => $request->deskripsi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil dibuat',
                'data' => $barang
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}