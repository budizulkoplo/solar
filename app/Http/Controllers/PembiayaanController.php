<?php

namespace App\Http\Controllers;

use App\Models\Pembiayaan;
use App\Models\PembiayaanLog;
use App\Models\PembiayaanDokumen;
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
        return view('transaksi.pembiayaan.index', compact('type'));
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
            'project:id,nama_project',
            'creator:id,name'
        ])
        ->where('idcompany', $companyId);
        
        // Filter berdasarkan jenis
        if ($type === 'company') {
            $query->companyOnly();
        } elseif ($type === 'project') {
            $query->projectOnly();
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('action', function($row) {
                $user = auth()->user();
                $canDelete = $user->hasRole('direktur') || $user->hasRole('keuangan');
                $canApprove = $user->hasRole('direktur') || $user->hasRole('keuangan');
                
                $actionBtn = '<div class="btn-group">';
                $actionBtn .= '<button class="btn btn-sm btn-info view-btn" data-id="'.$row->id.'"><i class="bi bi-eye"></i></button>';
                
                // Hanya bisa edit jika status draft
                if ($row->status === 'draft') {
                    $actionBtn .= '<button class="btn btn-sm btn-warning edit-btn" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>';
                }
                
                // Tombol approval
                if ($canApprove && in_array($row->status, ['draft', 'approved'])) {
                    if ($row->status === 'draft') {
                        $actionBtn .= '<button class="btn btn-sm btn-success approve-btn" data-id="'.$row->id.'" title="Approve"><i class="bi bi-check-circle"></i></button>';
                        $actionBtn .= '<button class="btn btn-sm btn-danger reject-btn" data-id="'.$row->id.'" title="Reject"><i class="bi bi-x-circle"></i></button>';
                    } elseif ($row->status === 'approved') {
                        $actionBtn .= '<button class="btn btn-sm btn-primary complete-btn" data-id="'.$row->id.'" title="Complete"><i class="bi bi-arrow-right-circle"></i></button>';
                    }
                }
                
                // Tombol delete
                if ($canDelete && in_array($row->status, ['draft', 'rejected'])) {
                    $actionBtn .= '<button class="btn btn-sm btn-danger delete-btn" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>';
                }
                
                $actionBtn .= '</div>';
                
                return $actionBtn;
            })
            ->addColumn('target', function($row) {
                if ($row->jenis === 'company') {
                    return $row->company ? $row->company->company_name : '-';
                } else {
                    return $row->project ? $row->project->nama_project : '-';
                }
            })
            ->editColumn('tanggal', function($row) {
                return date('d/m/Y', strtotime($row->tanggal));
            })
            ->editColumn('nominal', function($row) {
                return 'Rp ' . number_format($row->nominal, 0, ',', '.');
            })
            ->editColumn('status', function($row) {
                $badge = [
                    'draft' => 'bg-secondary',
                    'approved' => 'bg-primary',
                    'completed' => 'bg-success',
                    'rejected' => 'bg-danger'
                ];
                return '<span class="badge '.$badge[$row->status].'">'.ucfirst($row->status).'</span>';
            })
            ->editColumn('metode_pembayaran', function($row) {
                return ucfirst($row->metode_pembayaran);
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
                            $q->where('nama_project', 'like', "%{$search}%");
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
        $projects = Project::where('idcompany', $companyId)->get();
        
        return response()->json([
            'success' => true,
            'type' => $type,
            'projects' => $projects
        ]);
    }

    // Simpan pembiayaan
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'jenis' => 'required|in:company,project',
                'idproject' => 'required_if:jenis,project|nullable|exists:projects,id',
                'rekening_id' => 'required|exists:rekening,idrek',
                'nominal' => 'required|numeric|min:1',
                'tanggal' => 'required|date',
                'deskripsi' => 'nullable|string|max:1000',
                'metode_pembayaran' => 'required|in:cash,transfer',
                'dokumen' => 'nullable|array',
                'dokumen.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            // Validasi rekening milik company
            $rekening = Rekening::where('idrek', $request->rekening_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik company ini");
            }

            // Jika project, validasi project milik company
            if ($request->jenis === 'project') {
                $project = Project::where('id', $request->idproject)
                    ->where('idcompany', $companyId)
                    ->first();
                
                if (!$project) {
                    throw new \Exception("Project tidak ditemukan atau bukan milik company ini");
                }
            }

            // Generate kode pembiayaan
            $kodePembiayaan = 'FUND-' . strtoupper($request->jenis) . '-' . $companyId . '-' . 
                              date('Ymd') . '-' . rand(1000, 9999);

            // Simpan pembiayaan
            $pembiayaan = Pembiayaan::create([
                'kode_pembiayaan' => $kodePembiayaan,
                'judul' => $request->judul,
                'jenis' => $request->jenis,
                'idcompany' => $companyId,
                'idproject' => $request->jenis === 'project' ? $request->idproject : null,
                'rekening_id' => $request->rekening_id,
                'nominal' => $request->nominal,
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status' => 'draft',
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

            // Buat log
            $this->createLog($pembiayaan->id, 'create', 
                "Pembiayaan dibuat: {$kodePembiayaan}, " .
                "Judul: {$request->judul}, " .
                "Nominal: Rp " . number_format($request->nominal, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil disimpan sebagai draft',
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
                'logs' => function($q) {
                    $q->with('user:id,name')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $pembiayaan
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
            
            // Hanya bisa edit jika status draft
            if ($pembiayaan->status !== 'draft') {
                throw new \Exception("Hanya pembiayaan dengan status draft yang dapat diedit");
            }

            $companyId = session('active_company_id');
            $projects = Project::where('idcompany', $companyId)->get();

            return response()->json([
                'success' => true,
                'data' => $pembiayaan,
                'projects' => $projects
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

    // Update pembiayaan
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'jenis' => 'required|in:company,project',
                'idproject' => 'required_if:jenis,project|nullable|exists:projects,id',
                'rekening_id' => 'required|exists:rekening,idrek',
                'nominal' => 'required|numeric|min:1',
                'tanggal' => 'required|date',
                'deskripsi' => 'nullable|string|max:1000',
                'metode_pembayaran' => 'required|in:cash,transfer',
                'dokumen' => 'nullable|array',
                'dokumen.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
                'deleted_files' => 'nullable|array'
            ]);

            $companyId = session('active_company_id');
            $user = Auth::user();

            // Cari pembiayaan lama
            $pembiayaan = Pembiayaan::findOrFail($id);
            
            // Hanya bisa edit jika status draft
            if ($pembiayaan->status !== 'draft') {
                throw new \Exception("Hanya pembiayaan dengan status draft yang dapat diedit");
            }

            // Simpan data lama untuk log
            $oldData = $pembiayaan->toArray();

            // Validasi rekening milik company
            $rekening = Rekening::where('idrek', $request->rekening_id)
                ->where('idcompany', $companyId)
                ->first();

            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan atau bukan milik company ini");
            }

            // Jika project, validasi project milik company
            if ($request->jenis === 'project') {
                $project = Project::where('id', $request->idproject)
                    ->where('idcompany', $companyId)
                    ->first();
                
                if (!$project) {
                    throw new \Exception("Project tidak ditemukan atau bukan milik company ini");
                }
            }

            // Update pembiayaan
            $pembiayaan->update([
                'judul' => $request->judul,
                'jenis' => $request->jenis,
                'idproject' => $request->jenis === 'project' ? $request->idproject : null,
                'rekening_id' => $request->rekening_id,
                'nominal' => $request->nominal,
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->deskripsi,
                'metode_pembayaran' => $request->metode_pembayaran
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

            // Buat log perubahan
            $changes = $this->getChangesForLog($oldData, $pembiayaan->toArray());
            if (!empty($changes)) {
                $logMessage = "Pembiayaan diupdate: " . implode(", ", $changes);
                $this->createLog($pembiayaan->id, 'update', $logMessage);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil diupdate',
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

    // Approve pembiayaan
    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $pembiayaan = Pembiayaan::findOrFail($id);
            $user = Auth::user();

            // Cek role user untuk approval
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                throw new \Exception("Anda tidak memiliki izin untuk melakukan approval");
            }

            // Hanya bisa approve jika status draft
            if ($pembiayaan->status !== 'draft') {
                throw new \Exception("Hanya pembiayaan dengan status draft yang dapat di-approve");
            }

            // Update status
            $pembiayaan->update(['status' => 'approved']);

            // Buat log
            $this->createLog($pembiayaan->id, 'approve', 
                "Pembiayaan di-approve oleh {$user->name}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil di-approve'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error approve pembiayaan:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Reject pembiayaan
    public function reject($id)
    {
        DB::beginTransaction();
        try {
            $pembiayaan = Pembiayaan::findOrFail($id);
            $user = Auth::user();

            // Cek role user untuk rejection
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                throw new \Exception("Anda tidak memiliki izin untuk melakukan rejection");
            }

            // Hanya bisa reject jika status draft
            if ($pembiayaan->status !== 'draft') {
                throw new \Exception("Hanya pembiayaan dengan status draft yang dapat di-reject");
            }

            // Update status
            $pembiayaan->update(['status' => 'rejected']);

            // Buat log
            $this->createLog($pembiayaan->id, 'reject', 
                "Pembiayaan di-reject oleh {$user->name}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil di-reject'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error reject pembiayaan:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Complete pembiayaan (proses penambahan saldo)
    public function complete($id)
    {
        DB::beginTransaction();
        try {
            $pembiayaan = Pembiayaan::with('rekening')->findOrFail($id);
            $user = Auth::user();

            // Cek role user untuk completion
            if (!$user->hasRole('direktur') && !$user->hasRole('keuangan')) {
                throw new \Exception("Anda tidak memiliki izin untuk menyelesaikan pembiayaan");
            }

            // Hanya bisa complete jika status approved
            if ($pembiayaan->status !== 'approved') {
                throw new \Exception("Hanya pembiayaan dengan status approved yang dapat diselesaikan");
            }

            // Proses penambahan saldo
            $rekening = $pembiayaan->rekening;
            if (!$rekening) {
                throw new \Exception("Rekening tidak ditemukan");
            }

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

            // Update status pembiayaan
            $pembiayaan->update(['status' => 'completed']);

            // Buat log
            $this->createLog($pembiayaan->id, 'complete', 
                "Pembiayaan diselesaikan oleh {$user->name}. " .
                "Saldo bertambah: Rp " . number_format($pembiayaan->nominal, 0, ',', '.') . 
                ", Saldo akhir: Rp " . number_format($rekening->saldo, 0, ',', '.'));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembiayaan berhasil diselesaikan dan saldo telah ditambahkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error complete pembiayaan:', [
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
            $pembiayaan = Pembiayaan::with('dokumen')->findOrFail($id);
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

    // Ambil project berdasarkan company
    public function getProjects()
    {
        try {
            $companyId = session('active_company_id');
            $projects = Project::where('idcompany', $companyId)
                ->select('id', 'nama_project')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $projects
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
            'jenis' => 'Jenis',
            'idproject' => 'Project',
            'rekening_id' => 'Rekening',
            'nominal' => 'Nominal',
            'tanggal' => 'Tanggal',
            'deskripsi' => 'Deskripsi',
            'metode_pembayaran' => 'Metode Pembayaran'
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
                    } elseif ($field === 'idproject') {
                        $oldProject = Project::find($oldValue);
                        $newProject = Project::find($newValue);
                        $oldValue = $oldProject ? $oldProject->nama_project : 'Tidak ada';
                        $newValue = $newProject ? $newProject->nama_project : 'Tidak ada';
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
}