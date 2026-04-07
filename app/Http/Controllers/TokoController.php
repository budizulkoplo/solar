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
use App\Models\Customer;
use App\Models\CustomerToko;
use App\Models\StockProject;
use App\Models\StockHistory;
use App\Models\TransUpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class TokoController extends Controller
{
    // Halaman transaksi pembelian (out)
    public function pembelian()
    {
        $kodeTransaksi = KodeTransaksi::orderBy('kodetransaksi')
            ->get(['id', 'kodetransaksi', 'transaksi']);

        return view('transaksi.toko.pembelian', compact('kodeTransaksi'));
    }

    // Halaman transaksi penjualan (in)
    public function penjualan()
    {
        $kodeTransaksi = KodeTransaksi::orderBy('kodetransaksi')
            ->get(['id', 'kodetransaksi', 'transaksi']);

        return view('transaksi.toko.penjualan', compact('kodeTransaksi'));
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
                'vendor:id,namavendor',
                'customer:id,nama_lengkap,no_hp',
                'customerToko:id,nama_lengkap,no_hp'
            ])
            ->where('type', 'toko')
            ->whereNotNull('jenis_penjualan')
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
            ->editColumn('type', function($row) {
                return $row->jenis_penjualan ?? 'toko';
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
                        ->orWhere('keterangan_customer', 'like', "%{$search}%")
                        ->orWhere('total', 'like', "%{$search}%")
                        ->orWhere('namauser', 'like', "%{$search}%")
                        ->orWhereHas('customerToko', function($q) use ($search) {
                            $q->where('nama_lengkap', 'like', "%{$search}%")
                              ->orWhere('no_hp', 'like', "%{$search}%")
                              ->orWhere('kode_customer', 'like', "%{$search}%");
                        })
                        ->orWhereHas('customer', function($q) use ($search) {
                            $q->where('nama_lengkap', 'like', "%{$search}%")
                              ->orWhere('no_hp', 'like', "%{$search}%");
                        })
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
                'paymen_method' => 'required|in:cash,tempo',
                'tgl_tempo' => 'nullable|date|required_if:paymen_method,tempo',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.qty' => 'required|numeric|min:0.01',
                'transactions.*.harga_beli' => 'required|numeric|min:0',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
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
                'cashflow' => 'out',
                'type' => 'toko',
                'paymen_method' => $request->paymen_method ?? 'cash',
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => 0,
                'diskon' => 0,
                'total' => $total,
                'status' => $request->paymen_method === 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi
            foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['qty'] * $transaction['harga_beli'];

                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => null,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['harga_beli'],
                    'jml' => $transaction['qty'],
                    'total' => $itemTotal,
                ]);
            }

            // Proses pembayaran
            if ($request->paymen_method === 'cash') {
                $this->processPayment($nota, $request->idrek, $total, $request->tanggal, 'out');
            }

            // Buat log
            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Pembelian barang - No: {$nota->nota_no}, Total: Rp " . number_format($total, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembelian berhasil disimpan',
                'nota_id' => $nota->id,
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
                'paymen_method' => 'required|in:cash,tempo',
                'tgl_tempo' => 'nullable|date|required_if:paymen_method,tempo',
                'customer_toko_id' => 'nullable|exists:customer_toko,id',
                'keterangan_customer' => 'nullable|string|max:255',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.qty' => 'required|numeric|min:0.01',
                'transactions.*.harga_jual' => 'required|numeric|min:0',
                'jenis_penjualan' => 'required|in:toko,project',
                'project_tujuan_id' => 'nullable|exists:projects,id|required_if:jenis_penjualan,project',
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
                'project_tujuan_id' => $request->project_tujuan_id,
                'idcompany' => $idcompany,
                'idretail' => $idretail,
                'idrek' => $request->idrek,
                'customer_toko_id' => $request->customer_toko_id,
                'keterangan_customer' => $request->keterangan_customer,
                'tanggal' => $request->tanggal,
                'cashflow' => 'in',
                'type' => 'toko',
                'jenis_penjualan' => $request->jenis_penjualan,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method == 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'ppn' => 0,
                'diskon' => 0,
                'total' => $total,
                'status' => $request->paymen_method === 'cash' ? 'paid' : 'open',
                'bukti_nota' => null,
                'nip' => $nip,
                'namauser' => $namauser,
            ];

            // Buat nota header
            $nota = Nota::create($notaData);

            // Simpan detail transaksi penjualan berbasis kode transaksi
            foreach ($request->transactions as $transaction) {
                $itemTotal = $transaction['qty'] * $transaction['harga_jual'];

                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => null,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['harga_jual'],
                    'jml' => $transaction['qty'],
                    'total' => $itemTotal,
                ]);
            }

            // Proses pembayaran
            if ($request->paymen_method === 'cash') {
                $this->processPayment($nota, $request->idrek, $total, $request->tanggal, 'in');
            }

            // Buat log
            $customerName = null;
            if ($request->customer_toko_id) {
                $customerName = CustomerToko::find($request->customer_toko_id)?->nama_lengkap;
            }

            $this->createUpdateLog($nota->id, $nota->nota_no, 
                "Penjualan {$request->jenis_penjualan} ({$request->paymen_method}) - No: {$nota->nota_no}, Total: Rp " .
                number_format($total, 0, ',', '.') .
                ($customerName ? ", Customer: {$customerName}" : ''));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penjualan berhasil disimpan',
                'nota_id' => $nota->id,
                'invoice_url' => route('toko.invoice', $nota->id),
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

    public function getCustomers(Request $request)
    {
        $search = $request->get('search');

        $customers = CustomerToko::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('kode_customer', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_lengkap')
            ->limit(20)
            ->get(['id', 'kode_customer', 'nama_lengkap', 'no_hp', 'alamat']);

        return response()->json($customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'text' => trim("{$customer->nama_lengkap} | {$customer->no_hp}"),
                'alamat' => $customer->alamat,
            ];
        })->values());
    }

    public function storeCustomer(Request $request)
    {
        $validator = validator($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'alamat' => 'required|string',
            'no_hp' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer = CustomerToko::create([
                'nama_lengkap' => $request->nama_lengkap,
                'alamat' => $request->alamat,
                'no_hp' => $request->no_hp,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer toko berhasil ditambahkan',
                'data' => $customer,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan customer: ' . $e->getMessage(),
            ], 500);
        }
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
                'customer',
                'customerToko',
                'projectTujuan',
                'rekening',
                'transactions' => function($q) {
                    $q->with(['barang', 'kodeTransaksi'])
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
                'customer',
                'customerToko',
                'projectTujuan',
                'transactions' => function($q) {
                    $q->with(['barang', 'kodeTransaksi'])
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

    public function update(Request $request, $id)
    {
        $nota = Nota::findOrFail($id);

        if ($this->isPembelianNota($nota)) {
            return $this->updatePembelian($request, $id);
        }

        return $this->updatePenjualan($request, $id);
    }

    private function updatePembelian(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'tgl_tempo' => 'nullable|date|required_if:paymen_method,tempo',
                'vendor_id' => 'nullable|exists:vendors,id',
                'bukti_nota' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.qty' => 'required|numeric|min:0.01',
                'transactions.*.harga_beli' => 'required|numeric|min:0',
            ]);

            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);
            $projectId = session('active_project_id');
            $project = Project::find($projectId);

            if (!$project) {
                throw new \Exception("Project toko tidak ditemukan");
            }

            $buktiNotaPath = $nota->bukti_nota;
            if ($request->hasFile('bukti_nota')) {
                if ($buktiNotaPath && Storage::disk('public')->exists($buktiNotaPath)) {
                    Storage::disk('public')->delete($buktiNotaPath);
                }

                $file = $request->file('bukti_nota');
                $filename = 'nota_toko_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiNotaPath = $file->storeAs('bukti_nota', $filename, 'public');
            }

            $subtotal = collect($request->transactions)->sum(function ($transaction) {
                return (float) $transaction['qty'] * (float) $transaction['harga_beli'];
            });

            $this->rollbackPayments($nota);

            NotaTransaction::where('idnota', $nota->id)->delete();

            $nota->update([
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'vendor_id' => $request->vendor_id,
                'idrek' => $request->idrek,
                'tanggal' => $request->tanggal,
                'cashflow' => 'out',
                'type' => 'toko',
                'jenis_penjualan' => null,
                'project_tujuan_id' => null,
                'customer_toko_id' => null,
                'keterangan_customer' => null,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method === 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => $request->paymen_method === 'cash' ? 'paid' : 'open',
                'bukti_nota' => $buktiNotaPath,
            ]);

            foreach ($request->transactions as $transaction) {
                $itemTotal = (float) $transaction['qty'] * (float) $transaction['harga_beli'];

                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => null,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['harga_beli'],
                    'jml' => $transaction['qty'],
                    'total' => $itemTotal,
                ]);
            }

            if ($request->paymen_method === 'cash') {
                $this->processPayment($nota, $request->idrek, $subtotal, $request->tanggal, 'out');
            }

            $this->createUpdateLog(
                $nota->id,
                $nota->nota_no,
                "Pembelian diupdate - No: {$nota->nota_no}, Total: Rp " .
                number_format($subtotal, 0, ',', '.')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pembelian berhasil diupdate',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function updatePenjualan(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'nota_no' => 'required|string|max:50',
                'namatransaksi' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'idrek' => 'required|exists:rekening,idrek',
                'paymen_method' => 'required|in:cash,tempo',
                'tgl_tempo' => 'nullable|date|required_if:paymen_method,tempo',
                'customer_toko_id' => 'nullable|exists:customer_toko,id',
                'keterangan_customer' => 'nullable|string|max:255',
                'transactions' => 'required|array|min:1',
                'transactions.*.idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'transactions.*.description' => 'required|string|max:255',
                'transactions.*.qty' => 'required|numeric|min:0.01',
                'transactions.*.harga_jual' => 'required|numeric|min:0',
                'jenis_penjualan' => 'required|in:toko,project',
                'project_tujuan_id' => 'nullable|exists:projects,id|required_if:jenis_penjualan,project',
            ]);

            $nota = Nota::with(['payments', 'cashflows'])->findOrFail($id);
            $projectToko = Project::find(session('active_project_id'));

            if (!$projectToko) {
                throw new \Exception("Project toko tidak ditemukan");
            }

            $subtotal = collect($request->transactions)->sum(function ($transaction) {
                return (float) $transaction['qty'] * (float) $transaction['harga_jual'];
            });

            $this->rollbackPayments($nota);

            $nota->update([
                'nota_no' => $request->nota_no,
                'namatransaksi' => $request->namatransaksi,
                'project_tujuan_id' => $request->project_tujuan_id,
                'idrek' => $request->idrek,
                'customer_toko_id' => $request->customer_toko_id,
                'keterangan_customer' => $request->keterangan_customer,
                'tanggal' => $request->tanggal,
                'cashflow' => 'in',
                'jenis_penjualan' => $request->jenis_penjualan,
                'paymen_method' => $request->paymen_method,
                'tgl_tempo' => $request->paymen_method === 'tempo' ? $request->tgl_tempo : null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'status' => $request->paymen_method === 'cash' ? 'paid' : 'open',
            ]);

            NotaTransaction::where('idnota', $nota->id)->delete();
            foreach ($request->transactions as $transaction) {
                NotaTransaction::create([
                    'idnota' => $nota->id,
                    'idbarang' => null,
                    'idkodetransaksi' => $transaction['idkodetransaksi'],
                    'description' => $transaction['description'],
                    'nominal' => $transaction['harga_jual'],
                    'jml' => $transaction['qty'],
                    'total' => $transaction['qty'] * $transaction['harga_jual'],
                ]);
            }

            StockHistory::where('idnota', $nota->id)->delete();

            if ($request->paymen_method === 'cash') {
                $this->processPayment($nota, $request->idrek, $subtotal, $request->tanggal, 'in');
            }

            $customerName = $request->customer_toko_id
                ? CustomerToko::find($request->customer_toko_id)?->nama_lengkap
                : null;

            $this->createUpdateLog(
                $nota->id,
                $nota->nota_no,
                "Penjualan diupdate - No: {$nota->nota_no}, Total: Rp " .
                number_format($subtotal, 0, ',', '.') .
                ($customerName ? ", Customer: {$customerName}" : '')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi penjualan berhasil diupdate',
                'invoice_url' => route('toko.invoice', $nota->id),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function invoicePdf($id)
    {
        $nota = Nota::with([
            'project.companyUnit',
            'vendor',
            'customerToko',
            'rekening',
            'transactions.kodeTransaksi',
            'transactions.barang',
        ])->findOrFail($id);

        $company = $nota->project?->companyUnit;
        $logoPath = null;

        if (!empty($company?->logo)) {
            $candidate = public_path('storage/' . $company->logo);
            if (file_exists($candidate)) {
                $logoPath = $candidate;
            }
        }

        $pdf = \PDF::loadView('transaksi.toko.invoice_pdf', [
            'nota' => $nota,
            'company' => $company,
            'logoPath' => $logoPath,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('invoice-' . $nota->nota_no . '.pdf');
    }

    private function rollbackPayments(Nota $nota): void
    {
        $cashflows = Cashflow::where('idnota', $nota->id)->get();

        foreach ($cashflows as $cashflow) {
            $rekening = Rekening::find($cashflow->idrek);
            if (!$rekening) {
                continue;
            }

            if ($cashflow->cashflow === 'in') {
                $rekening->saldo -= $cashflow->nominal;
            } else {
                $rekening->saldo += $cashflow->nominal;
            }

            $rekening->save();
        }

        NotaPayment::where('idnota', $nota->id)->delete();
        Cashflow::where('idnota', $nota->id)->delete();
    }

    private function isPembelianNota(Nota $nota): bool
    {
        return empty($nota->jenis_penjualan);
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
