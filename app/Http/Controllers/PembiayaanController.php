<?php

namespace App\Http\Controllers;

use App\Models\Pembiayaan;
use App\Models\PembiayaanLog;
use App\Models\PembiayaanDokumen;
use App\Models\PembiayaanSetoran;
use App\Models\Rekening;
use App\Models\CompanyUnit;
use App\Models\Project;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class PembiayaanController extends Controller
{
    public function index(string $type = 'company')
    {
        // Untuk project, ambil dari session jika ada
        $projectId = session('active_project_id');
        $projectName = session('active_project_name');
        
        return view('transaksi.pembiayaan.index', compact('type', 'projectId', 'projectName'));
    }
    
    // Datatable untuk pembiayaan
    public function getdata($type)
    {
        $companyId = session('active_company_id');
        
        if (!$companyId) {
            return DataTables::collection(collect([]))->toJson();
        }

        $query = Pembiayaan::with([
            'rekening:idrek,norek,namarek',
            'company:id,company_name',
            'project:id,namaproject',
            'creator:id,name',
            'setorans' => function($q) {
                $q->where('status', 'paid');
            }
        ])
        ->where('idcompany', $companyId);
        
        // Filter berdasarkan jenis
        if ($type === 'company') {
            $query->companyOnly();
        } elseif ($type === 'project') {
            $query->projectOnly();
            
            // Filter berdasarkan project yang aktif di session
            $projectId = session('active_project_id');
            if ($projectId) {
                $query->where('idproject', $projectId);
            }
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $actionBtn = '<div class="btn-group">';
                $actionBtn .= '<button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>';
                
                // Hanya bisa edit/hapus jika status completed
                if (in_array($row->status, ['draft', 'rejected'])) {
                    $actionBtn .= '<button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>';
                    
                    if ($canDelete) {
                        $actionBtn .= '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>';
                    }
                }
                
                // Tombol setoran untuk pembiayaan yang status completed
                if ($row->status === 'completed') {
                    $actionBtn .= '<button class="btn btn-sm btn-success setoran-btn" data-id="'.$row->id.'" title="Setoran"><i class="bi bi-cash-coin"></i></button>';
                }
                
                $actionBtn .= '</div>';
                
                return $actionBtn;
            })
            ->addColumn('target', function($row) {
                if ($row->jenis === 'company') {
                    return $row->company ? $row->company->company_name : '-';
                } else {
                    return $row->project ? $row->project->namaproject : '-';
                }
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('nominal', function($row) {
                return 'Rp ' . number_format($row->nominal, 0, ',', '.');
            })
            ->addColumn('terbayar', function($row) {
                $totalSetoran = $row->setorans->sum('pokok');
                return 'Rp ' . number_format($totalSetoran, 0, ',', '.');
            })
            ->addColumn('sisa', function($row) {
                $totalSetoran = $row->setorans->sum('pokok');
                $sisa = $row->nominal - $totalSetoran;
                return 'Rp ' . number_format($sisa, 0, ',', '.');
            })
            ->editColumn('status', function($row) {
                // Hitung total pokok terbayar
                $totalSetoran = $row->setorans->sum('pokok');
                $sisa = $row->nominal - $totalSetoran;
                
                // Update status otomatis jika sudah lunas
                if ($sisa <= 0 && $row->status === 'completed') {
                    $row->update(['status' => 'lunas']);
                    return '<span class="badge bg-success">Lunas</span>';
                }
                
                $badge = [
                    'draft' => 'bg-secondary',
                    'completed' => 'bg-primary',
                    'lunas' => 'bg-success',
                    'rejected' => 'bg-danger'
                ];
                $statusText = $row->status === 'completed' && $sisa > 0 ? 'Aktif' : ucfirst($row->status);
                return '<span class="badge '.$badge[$row->status].'">'.$statusText.'</span>';
            })
            ->addColumn('user', function($row) {
                return $row->creator ? $row->creator->name : '-';
            })
            ->filter(function($query) use ($type, $companyId) {
                $search = request('search.value');
                
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('kode_pembiayaan', 'like', "%{$search}%")
                        ->orWhere('judul', 'like', "%{$search}%")
                        ->orWhere('nominal', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhereHas('rekening', function($q) use ($search) {
                            $q->where('norek', 'like', "%{$search}%")
                              ->orWhere('namarek', 'like', "%{$search}%");
                        })
                        ->orWhereHas('project', function($q) use ($search) {
                            $q->where('namaproject', 'like', "%{$search}%");
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

    // Tampilkan form create
    public function create($type = 'company')
    {
        $companyId = session('active_company_id');
        
        // Untuk project, ambil dari session
        $projectId = null;
        if ($type === 'project') {
            $projectId = session('active_project_id');
            if (!$projectId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada project yang dipilih. Silakan pilih project terlebih dahulu.'
                ], 400);
            }
        }
        
        return response()->json([
            'success' => true,
            'type' => $type,
            'project_id' => $projectId
        ]);
    }

    // Simpan pembiayaan (langsung complete)
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'jenis' => 'required|in:company,project',
                'rekening_id' => 'required|exists:rekening,idrek',
                'nominal' => 'required|numeric|min:1',
                'tanggal' => 'required|date',
                'deskripsi' => 'nullable|string|max:1000',
                'dokumen' => 'nullable|array',
                'dokumen.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            // Untuk project, ambil dari session
            $projectId = null;
            if ($request->jenis === 'project') {
                $projectId = session('active_project_id');
                if (!$projectId) {
                    throw new \Exception("Tidak ada project yang dipilih. Silakan pilih project terlebih dahulu.");
                }
                
                // Validasi project milik company
                $project = Project::where('id', $projectId)
                    ->where('idcompany', $companyId)
                    ->first();
                
                if (!$project) {
                    throw new \Exception("Project tidak ditemukan atau bukan milik company ini");
                }
            }

            // Validasi rekening milik company
            $rekening = Rekening::where('idrek', $request->rekening_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik company ini");
            }

            // Generate kode pembiayaan
            $kodePembiayaan = 'FUND-' . strtoupper($request->jenis) . '-' . $companyId . '-' . 
                              date('Ymd') . '-' . rand(1000, 9999);

            // Simpan pembiayaan langsung dengan status completed
            $pembiayaan = Pembiayaan::create([
                'kode_pembiayaan' => $kodePembiayaan,
                'judul' => $request->judul,
                'jenis' => $request->jenis,
                'idcompany' => $companyId,
                'idproject' => $projectId,
                'rekening_id' => $request->rekening_id,
                'nominal' => $request->nominal,
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi,
                'metode_pembayaran' => 'transfer', // Default
                'status' => 'completed',
                'created_by' => $user->id
            ]);

            // Upload dokumen jika ada
            if ($request->hasFile('dokumen')) {
                foreach ($request->file('dokumen') as $file) {
                    $filename = 'pembiayaan_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('pembiayaan_dokumen', $filename, 'public');
                    
                    PembiayaanDokumen::create([
                        'pembiayaan_id' => $pembiayaan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                        'tipe_file' => $file->getMimeType(),
                        'size_file' => $file->getSize(),
                        'created_by' => $user->id
                    ]);
                }
            }

            // Proses penambahan saldo rekening
            $saldoAwal = $rekening->saldo;
            $rekening->saldo += $pembiayaan->nominal;
            $rekening->save();

            // Catat di cashflow
            Cashflow::create([
                'idrek' => $rekening->idrek,
                'tanggal' => $pembiayaan->tanggal,
                'cashflow' => 'in',
                'nominal' => $pembiayaan->nominal,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembiayaan: {$pembiayaan->judul} ({$pembiayaan->kode_pembiayaan})",
                'kode_transaksi' => $pembiayaan->kode_pembiayaan
            ]);

            // Buat log
            $this->createLog($pembiayaan->id, 'create', 
                "Pembiayaan dibuat dan langsung diproses: {$kodePembiayaan}, " .
                "Judul: {$request->judul}, " .
                "Nominal: Rp " . number_format($request->nominal, 0, ',', '.') . ", " .
                "Saldo bertambah: Rp " . number_format($request->nominal, 0, ',', '.') . 
                ", Saldo akhir: Rp " . number_format($rekening->saldo, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil disimpan dan saldo telah ditambahkan',
                'data' => $pembiayaan
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error store pembiayaan:', [
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

    // Tampilkan detail pembiayaan
    public function show($id)
    {
        try {
            $pembiayaan = Pembiayaan::with([
                'rekening',
                'company',
                'project',
                'creator',
                'dokumen',
                'setorans' => function($q) {
                    $q->orderBy('tanggal', 'desc');
                },
                'logs' => function($q) {
                    $q->with('user:id,name')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            // Hitung total setoran
            $totalSetoran = $pembiayaan->setorans->where('status', 'paid')->sum('pokok');
            $sisa = $pembiayaan->nominal - $totalSetoran;

            return response()->json([
                'success' => true,
                'data' => $pembiayaan,
                'summary' => [
                    'total_setoran' => $totalSetoran,
                    'sisa' => $sisa,
                    'persentase' => $pembiayaan->nominal > 0 ? ($totalSetoran / $pembiayaan->nominal) * 100 : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pembiayaan tidak ditemukan'
            ], 404);
        }
    }

    // Ambil data untuk edit
    public function edit($id)
    {
        try {
            $pembiayaan = Pembiayaan::with(['dokumen'])->findOrFail($id);
            
            // Hanya bisa edit jika status draft atau rejected
            if (!in_array($pembiayaan->status, ['draft', 'rejected'])) {
                throw new \Exception("Hanya pembiayaan dengan status draft atau rejected yang dapat diedit");
            }

            // Cek jika sudah ada setoran
            $totalSetoran = PembiayaanSetoran::where('pembiayaan_id', $id)->where('status', 'paid')->sum('pokok');
            if ($totalSetoran > 0) {
                throw new \Exception("Pembiayaan sudah memiliki setoran, tidak dapat diedit");
            }

            return response()->json([
                'success' => true,
                'data' => $pembiayaan
            ]);

        } catch (\Exception $e) {
            \Log::error('Error edit pembiayaan:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Update pembiayaan dengan penyesuaian saldo
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'rekening_id' => 'required|exists:rekening,idrek',
                'nominal' => 'required|numeric|min:1',
                'tanggal' => 'required|date',
                'deskripsi' => 'nullable|string|max:1000',
                'dokumen' => 'nullable|array',
                'dokumen.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
                'deleted_files' => 'nullable|array'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            // Cari pembiayaan lama
            $pembiayaan = Pembiayaan::with(['rekening'])->findOrFail($id);
            
            // Hanya bisa edit jika status draft atau rejected
            if (!in_array($pembiayaan->status, ['draft', 'rejected'])) {
                throw new \Exception("Hanya pembiayaan dengan status draft atau rejected yang dapat diedit");
            }

            // Cek jika sudah ada setoran
            $totalSetoran = PembiayaanSetoran::where('pembiayaan_id', $id)->where('status', 'paid')->sum('pokok');
            if ($totalSetoran > 0) {
                throw new \Exception("Pembiayaan sudah memiliki setoran, tidak dapat diedit");
            }

            // Simpan data lama untuk log
            $oldData = $pembiayaan->toArray();
            $oldNominal = $pembiayaan->nominal;

            // Validasi rekening milik company
            $rekening = Rekening::where('idrek', $request->rekening_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik company ini");
            }

            // Hitung selisih nominal untuk penyesuaian saldo
            $selisihNominal = $request->nominal - $oldNominal;

            // Update pembiayaan
            $pembiayaan->update([
                'judul' => $request->judul,
                'rekening_id' => $request->rekening_id,
                'nominal' => $request->nominal,
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi
            ]);

            // Hapus file yang dihapus
            if ($request->has('deleted_files')) {
                foreach ($request->deleted_files as $fileId) {
                    $dokumen = PembiayaanDokumen::find($fileId);
                    if ($dokumen) {
                        Storage::disk('public')->delete($dokumen->path_file);
                        $dokumen->delete();
                    }
                }
            }

            // Upload dokumen baru jika ada
            if ($request->hasFile('dokumen')) {
                foreach ($request->file('dokumen') as $file) {
                    $filename = 'pembiayaan_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('pembiayaan_dokumen', $filename, 'public');
                    
                    PembiayaanDokumen::create([
                        'pembiayaan_id' => $pembiayaan->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $path,
                        'tipe_file' => $file->getMimeType(),
                        'size_file' => $file->getSize(),
                        'created_by' => $user->id
                    ]);
                }
            }

            // PENYESUAIAN SALDO jika nominal berubah
            if ($selisihNominal != 0) {
                $saldoAwal = $rekening->saldo;
                $rekening->saldo += $selisihNominal;
                $rekening->save();
                
                // Catat cashflow untuk penyesuaian
                $cashflowType = $selisihNominal > 0 ? 'in' : 'out';
                $cashflowNominal = abs($selisihNominal);
                
                Cashflow::create([
                    'idrek' => $rekening->idrek,
                    'tanggal' => $pembiayaan->tanggal,
                    'cashflow' => $cashflowType,
                    'nominal' => $cashflowNominal,
                    'saldo_awal' => $saldoAwal,
                    'saldo_akhir' => $rekening->saldo,
                    'keterangan' => "Penyesuaian Pembiayaan: {$pembiayaan->judul} " . 
                                   ($selisihNominal > 0 ? "(Penambahan)" : "(Pengurangan)"),
                    'kode_transaksi' => $pembiayaan->kode_pembiayaan . '-ADJ'
                ]);
            }

            // Buat log perubahan
            $changes = $this->getChangesForLog($oldData, $pembiayaan->toArray());
            if (!empty($changes)) {
                $logMessage = "Pembiayaan diupdate: " . implode(", ", $changes);
                if ($selisihNominal != 0) {
                    $logMessage .= ". Saldo disesuaikan: " . ($selisihNominal > 0 ? "+" : "") . 
                                  "Rp " . number_format($selisihNominal, 0, ',', '.');
                }
                $this->createLog($pembiayaan->id, 'update', $logMessage);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil diupdate' . ($selisihNominal != 0 ? ' dan saldo telah disesuaikan' : ''),
                'data' => $pembiayaan
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error update pembiayaan:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Hapus pembiayaan
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $pembiayaan = Pembiayaan::with(['dokumen', 'rekening', 'setorans'])->findOrFail($id);
            $user = Auth::user();

            // Cek role user
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus pembiayaan'
                ], 403);
            }

            // Hanya bisa hapus jika status draft atau rejected
            if (!in_array($pembiayaan->status, ['draft', 'rejected'])) {
                throw new \Exception("Hanya pembiayaan dengan status draft atau rejected yang dapat dihapus");
            }

            // Cek jika sudah ada setoran
            $totalSetoran = $pembiayaan->setorans()->where('status', 'paid')->sum('pokok');
            if ($totalSetoran > 0) {
                throw new \Exception("Pembiayaan sudah memiliki setoran, tidak dapat dihapus");
            }

            // Kembalikan saldo jika sudah completed
            if ($pembiayaan->status === 'completed') {
                $rekening = $pembiayaan->rekening;
                if ($rekening) {
                    $saldoAwal = $rekening->saldo;
                    $rekening->saldo -= $pembiayaan->nominal;
                    $rekening->save();
                    
                    // Catat cashflow untuk pengembalian
                    Cashflow::create([
                        'idrek' => $rekening->idrek,
                        'tanggal' => date('Y-m-d'),
                        'cashflow' => 'out',
                        'nominal' => $pembiayaan->nominal,
                        'saldo_awal' => $saldoAwal,
                        'saldo_akhir' => $rekening->saldo,
                        'keterangan' => "Pembiayaan Dihapus: Pengembalian ({$pembiayaan->judul})",
                        'kode_transaksi' => $pembiayaan->kode_pembiayaan . '-DEL'
                    ]);
                }
            }

            // Hapus file dokumen
            foreach ($pembiayaan->dokumen as $dokumen) {
                Storage::disk('public')->delete($dokumen->path_file);
            }

            // Buat log penghapusan
            $this->createLog($pembiayaan->id, 'delete', 
                "Pembiayaan dihapus oleh {$user->name}");

            // Hapus pembiayaan
            $pembiayaan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error delete pembiayaan:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============ SETORAN FUNCTIONS ============ //

    // Ambil data untuk form setoran
    public function getSetoran($id)
    {
        try {
            $pembiayaan = Pembiayaan::findOrFail($id);
            
            // Hitung total setoran dan sisa
            $totalSetoran = PembiayaanSetoran::where('pembiayaan_id', $id)
                ->where('status', 'paid')
                ->sum('pokok');
            $sisa = $pembiayaan->nominal - $totalSetoran;
            
            // Ambil riwayat setoran
            $setorans = PembiayaanSetoran::where('pembiayaan_id', $id)
                ->orderBy('tanggal', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pembiayaan,
                'total_setoran' => $totalSetoran,
                'sisa' => $sisa,
                'setorans' => $setorans
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pembiayaan tidak ditemukan'
            ], 404);
        }
    }

    // Simpan setoran
    public function storeSetoran(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'tanggal' => 'required|date',
                'pokok' => 'required|numeric|min:1',
                'administrasi' => 'nullable|numeric|min:0',
                'margin' => 'nullable|numeric|min:0',
                'deskripsi' => 'nullable|string|max:1000',
                'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
            ]);

            $pembiayaan = Pembiayaan::with('rekening')->findOrFail($id);
            $user = Auth::user();

            // Validasi pembiayaan
            if ($pembiayaan->status !== 'completed') {
                throw new \Exception("Hanya pembiayaan dengan status completed yang dapat menerima setoran");
            }

            // Hitung sisa
            $totalSetoran = PembiayaanSetoran::where('pembiayaan_id', $id)
                ->where('status', 'paid')
                ->sum('pokok');
            $sisa = $pembiayaan->nominal - $totalSetoran;

            if ($request->pokok > $sisa) {
                throw new \Exception("Jumlah pokok setoran (Rp " . number_format($request->pokok, 0, ',', '.') . 
                                   ") melebihi sisa pembiayaans (Rp " . number_format($sisa, 0, ',', '.') . ")");
            }

            // Generate kode setoran
            $kodeSetoran = 'SETOR-' . $pembiayaan->kode_pembiayaan . '-' . date('Ymd') . '-' . rand(100, 999);

            // Upload bukti jika ada
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');
                $filename = 'setoran_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('pembiayaan_setoran', $filename, 'public');
            }

            // Hitung total
            $total = $request->pokok + ($request->administrasi ?? 0) + ($request->margin ?? 0);

            // Simpan setoran
            $setoran = PembiayaanSetoran::create([
                'pembiayaan_id' => $pembiayaan->id,
                'kode_setoran' => $kodeSetoran,
                'tanggal' => $request->tanggal,
                'pokok' => $request->pokok,
                'administrasi' => $request->administrasi ?? 0,
                'margin' => $request->margin ?? 0,
                'total' => $total,
                'deskripsi' => $request->deskripsi,
                'bukti_path' => $buktiPath,
                'status' => 'paid',
                'created_by' => $user->id
            ]);

            // Kurangi saldo rekening karena pembayaran hutang
            $rekening = $pembiayaan->rekening;
            if ($rekening) {
                $saldoAwal = $rekening->saldo;
                $rekening->saldo -= $setoran->total; // Mengurangi total (pokok + administrasi + margin)
                $rekening->save();

                // Catat di cashflow
                Cashflow::create([
                    'idrek' => $rekening->idrek,
                    'tanggal' => $setoran->tanggal,
                    'cashflow' => 'out',
                    'nominal' => $setoran->total,
                    'saldo_awal' => $saldoAwal,
                    'saldo_akhir' => $rekening->saldo,
                    'keterangan' => "Setoran Pembiayaan: {$pembiayaan->judul} ({$kodeSetoran})",
                    'kode_transaksi' => $kodeSetoran
                ]);
            }

            // Cek apakah sudah lunas
            $totalSetoranSetelah = $totalSetoran + $request->pokok;
            if ($totalSetoranSetelah >= $pembiayaan->nominal) {
                $pembiayaan->update(['status' => 'lunas']);
            }

            // Buat log
            $this->createLog($pembiayaan->id, 'setoran', 
                "Setoran diterima: {$kodeSetoran}, " .
                "Tanggal: " . date('d/m/Y', strtotime($request->tanggal)) . ", " .
                "Pokok: Rp " . number_format($request->pokok, 0, ',', '.') . ", " .
                "Administrasi: Rp " . number_format($request->administrasi ?? 0, 0, ',', '.') . ", " .
                "Margin: Rp " . number_format($request->margin ?? 0, 0, ',', '.') . ", " .
                "Total: Rp " . number_format($total, 0, ',', '.') . ", " .
                "Saldo berkurang: Rp " . number_format($total, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Setoran berhasil disimpan',
                'data' => $setoran,
                'summary' => [
                    'total_setoran' => $totalSetoranSetelah,
                    'sisa' => $pembiayaan->nominal - $totalSetoranSetelah
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error store setoran:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Hapus setoran
    public function deleteSetoran($id, $setoranId)
    {
        DB::beginTransaction();
        try {
            $setoran = PembiayaanSetoran::findOrFail($setoranId);
            $pembiayaan = Pembiayaan::findOrFail($id);
            $user = Auth::user();

            // Cek role user
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus setoran'
                ], 403);
            }

            // Kembalikan saldo
            $rekening = $pembiayaan->rekening;
            if ($rekening) {
                $saldoAwal = $rekening->saldo;
                $rekening->saldo += $setoran->total;
                $rekening->save();

                // Catat cashflow untuk pembatalan
                Cashflow::create([
                    'idrek' => $rekening->idrek,
                    'tanggal' => date('Y-m-d'),
                    'cashflow' => 'in',
                    'nominal' => $setoran->total,
                    'saldo_awal' => $saldoAwal,
                    'saldo_akhir' => $rekening->saldo,
                    'keterangan' => "Setoran Dibatalkan: {$pembiayaan->judul} ({$setoran->kode_setoran})",
                    'kode_transaksi' => $setoran->kode_setoran . '-CANCEL'
                ]);
            }

            // Hapus bukti file
            if ($setoran->bukti_path) {
                Storage::disk('public')->delete($setoran->bukti_path);
            }

            // Buat log
            $this->createLog($pembiayaan->id, 'setoran_cancel', 
                "Setoran dibatalkan: {$setoran->kode_setoran}, " .
                "Pokok: Rp " . number_format($setoran->pokok, 0, ',', '.') . ", " .
                "Total: Rp " . number_format($setoran->total, 0, ',', '.'));

            // Hapus setoran
            $setoran->delete();

            // Update status pembiayaan jika perlu
            $totalSetoran = PembiayaanSetoran::where('pembiayaan_id', $id)
                ->where('status', 'paid')
                ->sum('pokok');
            
            if ($totalSetoran < $pembiayaan->nominal && $pembiayaan->status === 'lunas') {
                $pembiayaan->update(['status' => 'completed']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Setoran berhasil dihapus',
                'summary' => [
                    'total_setoran' => $totalSetoran,
                    'sisa' => $pembiayaan->nominal - $totalSetoran
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error delete setoran:', [
                'message' => $e->getMessage(),
                'id' => $id,
                'setoran_id' => $setoranId
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

    // Ambil project berdasarkan session
    public function getProjectFromSession()
    {
        try {
            $projectId = session('active_project_id');
            $projectName = session('active_project_name');
            
            if (!$projectId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada project yang dipilih'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $projectId,
                    'namaproject' => $projectName
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Buat log
    private function createLog($pembiayaanId, $logType, $description)
    {
        return PembiayaanLog::create([
            'pembiayaan_id' => $pembiayaanId,
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
            'judul' => 'Judul',
            'rekening_id' => 'Rekening',
            'nominal' => 'Nominal',
            'tanggal' => 'Tanggal',
            'deskripsi' => 'Deskripsi'
        ];
        
        foreach ($fields as $field => $label) {
            if (isset($oldData[$field]) && isset($newData[$field])) {
                if ($oldData[$field] != $newData[$field]) {
                    $oldValue = $oldData[$field];
                    $newValue = $newData[$field];
                    
                    // Format khusus untuk beberapa field
                    if ($field === 'rekening_id') {
                        $oldRek = Rekening::find($oldValue);
                        $newRek = Rekening::find($newValue);
                        $oldValue = $oldRek ? $oldRek->norek . ' - ' . $oldRek->namarek : 'Tidak ada';
                        $newValue = $newRek ? $newRek->norek . ' - ' . $newRek->namarek : 'Tidak ada';
                    } elseif ($field === 'nominal') {
                        $oldValue = 'Rp ' . number_format($oldValue, 0, ',', '.');
                        $newValue = 'Rp ' . number_format($newValue, 0, ',', '.');
                    } elseif ($field === 'tanggal') {
                        $oldValue = date('d/m/Y', strtotime($oldValue));
                        $newValue = date('d/m/Y', strtotime($newValue));
                    }
                    
                    $changes[] = "{$label} diubah: {$oldValue} → {$newValue}";
                }
            }
        }
        
        return $changes;
    }

    public function getSetoranHistory($id)
{
    try {
        $pembiayaan = Pembiayaan::with(['setorans' => function($query) {
            $query->orderBy('tanggal', 'desc')
                  ->orderBy('created_at', 'desc');
        }])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $pembiayaan->setorans
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal memuat riwayat setoran'
        ], 500);
    }
}
}