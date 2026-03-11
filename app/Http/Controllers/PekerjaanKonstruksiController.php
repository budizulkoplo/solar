<?php

namespace App\Http\Controllers;

use App\Models\PekerjaanKonstruksi;
use App\Models\Project;
use App\Models\Kodetransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PekerjaanKonstruksiController extends Controller
{
    public function index()
    {
        $companyId = session('active_company_id');
        $projectId = session('active_project_id');

        if ($companyId) {
            $projects = Project::where('idcompany', $companyId)
                ->orderBy('namaproject')
                ->get(['id', 'namaproject']);

        } elseif ($projectId) {
           $projects = Project::where('idcompany', 3)
                ->orderBy('namaproject')
                ->get(['id', 'namaproject']);

        } else {
            $projects = Project::where('idcompany', 3)
                ->orderBy('namaproject')
                ->get(['id', 'namaproject']);
        }

        $kodeTransaksi = Kodetransaksi::orderBy('kodetransaksi')
            ->get(['id', 'kodetransaksi', 'transaksi']);
        $jenisPekerjaan = $kodeTransaksi->mapWithKeys(function ($item) {
            return [$item->id => $item->kodetransaksi . ' - ' . $item->transaksi];
        })->all();

        $statusPekerjaan = [
            'planning' => 'Planning',
            'ongoing' => 'Sedang Berjalan',
            'completed' => 'Selesai',
            'canceled' => 'Dibatalkan'
        ];

        return view('construction.index', compact('projects', 'kodeTransaksi', 'jenisPekerjaan', 'statusPekerjaan'));
    }

    public function getData(Request $request)
    {
        try {
            $query = PekerjaanKonstruksi::with(['project', 'kodeTransaksi'])
                ->select('pekerjaan_kontruksi.*');
            
            // Filter berdasarkan project jika ada
            if ($request->has('project_id') && !empty($request->project_id)) {
                $query->where('idproject', $request->project_id);
            }
            
            // Filter berdasarkan jenis pekerjaan
            $kodeTransaksiId = $request->idkodetransaksi ?? $request->jenis_pekerjaan;
            if (!empty($kodeTransaksiId)) {
                $query->where('idkodetransaksi', $kodeTransaksiId);
            }
            
            // Filter berdasarkan status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            $data = $query->orderBy('created_at', 'desc')->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('project', function($row) {
                    return $row->project ? $row->project->namaproject : '-';
                })
                ->addColumn('kode_transaksi_formatted', function($row) {
                    if ($row->kodeTransaksi) {
                        return '<span class="badge bg-info text-dark">'
                            . e($row->kodeTransaksi->kodetransaksi)
                            . '</span><br><small>'
                            . e($row->kodeTransaksi->transaksi)
                            . '</small>';
                    }

                    return '<span class="badge bg-secondary">-</span>';
                })
                ->addColumn('status_formatted', function($row) {
                    $status = [
                        'planning' => 'bg-secondary',
                        'ongoing' => 'bg-warning',
                        'completed' => 'bg-success',
                        'canceled' => 'bg-danger'
                    ];
                    return '<span class="badge '.($status[$row->status] ?? 'bg-light').'">'
                          .ucfirst($row->status).'</span>';
                })
                ->addColumn('anggaran_formatted', function($row) {
                    return 'Rp ' . number_format($row->anggaran ?? 0, 0, ',', '.');
                })
                ->addColumn('progress', function($row) {
                    if ($row->status === 'completed') {
                        return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%">100%</div>
                        </div>';
                    } elseif ($row->status === 'ongoing' && $row->tanggal_mulai && $row->tanggal_selesai) {
                        $progress = $row->getProgressAttribute();
                        return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: '.$progress.'%">'.round($progress, 0).'%</div>
                        </div>';
                    } else {
                        return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%">0%</div>
                        </div>';
                    }
                })
                ->addColumn('durasi', function($row) {
                    if ($row->tanggal_mulai && $row->tanggal_selesai) {
                        $start = date('d/m/Y', strtotime($row->tanggal_mulai));
                        $end = date('d/m/Y', strtotime($row->tanggal_selesai));
                        return $start . ' - ' . $end;
                    }
                    return '-';
                })
                ->addColumn('action', function($row) {
                    $buttons = '
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info viewPekerjaan" data-id="'.$row->id.'">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning editPekerjaan" data-id="'.$row->id.'">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger deletePekerjaan" data-id="'.$row->id.'">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                    return $buttons;
                })
                ->rawColumns(['kode_transaksi_formatted', 'status_formatted', 'progress', 'action'])
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error('Error in getData: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'idproject' => 'required|integer|exists:projects,id',
                'nama_pekerjaan' => 'required|string|max:255',
                'idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'lokasi' => 'nullable|string|max:255',
                'volume' => 'nullable|numeric|min:0',
                'satuan' => 'nullable|string|max:50',
                'anggaran' => 'required|numeric|min:0',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
                'harga_satuan' => 'nullable|numeric|min:0',
                'status' => 'required|in:planning,ongoing,completed,canceled',
                'keterangan' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $validated['jenis_pekerjaan'] = (string) $validated['idkodetransaksi'];
            $validated['jumlah'] = 0;

            $pekerjaan = PekerjaanKonstruksi::create($validated);

            // Tidak perlu generate detail seperti di Unit, karena pekerjaan konstruksi
            // adalah item tunggal dengan quantity tertentu
            // $jumlah adalah jumlah item/unit pekerjaan yang sama

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan konstruksi berhasil ditambahkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with('kodeTransaksi')->find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $pekerjaan
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with('kodeTransaksi')->find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            $validated = $request->validate([
                'idproject' => 'required|integer|exists:projects,id',
                'nama_pekerjaan' => 'required|string|max:255',
                'idkodetransaksi' => 'required|exists:kodetransaksi,id',
                'lokasi' => 'nullable|string|max:255',
                'volume' => 'nullable|numeric|min:0',
                'satuan' => 'nullable|string|max:50',
                'anggaran' => 'required|numeric|min:0',
                'tanggal_mulai' => 'nullable|date',
                'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
                'harga_satuan' => 'nullable|numeric|min:0',
                'status' => 'required|in:planning,ongoing,completed,canceled',
                'keterangan' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $validated['jenis_pekerjaan'] = (string) $validated['idkodetransaksi'];
            $validated['jumlah'] = 0;

            $pekerjaan->update($validated);

            // Update jumlah pekerjaan
            // Di sini tidak perlu handle detail seperti di Unit
            // $jumlah adalah quantity dari pekerjaan yang sama

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan konstruksi berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $pekerjaan = PekerjaanKonstruksi::find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            // Cek apakah pekerjaan sudah ongoing atau completed
            if (in_array($pekerjaan->status, ['ongoing', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menghapus pekerjaan yang sudah berjalan atau selesai'
                ], 400);
            }
            
            // Hapus pekerjaan (soft delete)
            $pekerjaan->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan konstruksi berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByProject($projectId)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with('kodeTransaksi')
                ->where('idproject', $projectId)
                ->where('status', '!=', 'canceled')
                ->whereNull('deleted_at')
                ->orderBy('nama_pekerjaan')
                ->get([
                    'id',
                    'nama_pekerjaan',
                    'jenis_pekerjaan',
                    'idkodetransaksi',
                    'lokasi',
                    'volume',
                    'satuan',
                    'anggaran',
                    'harga_satuan'
                ]);
            
            return response()->json([
                'success' => true,
                'data' => $pekerjaan
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getByProject: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:planning,ongoing,completed,canceled'
            ]);
            
            $pekerjaan = PekerjaanKonstruksi::find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan tidak ditemukan'
                ], 404);
            }
            
            $oldStatus = $pekerjaan->status;
            $pekerjaan->status = $request->status;
            $pekerjaan->save();
            
            \Log::info('Status pekerjaan diupdate: ' . $pekerjaan->nama_pekerjaan . ' ' . $oldStatus . ' → ' . $request->status);
            
            return response()->json([
                'success' => true,
                'message' => 'Status pekerjaan berhasil diupdate'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error update status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function report(Request $request)
    {
        $query = PekerjaanKonstruksi::with(['project', 'kodeTransaksi']);
        
        // Filter
        if ($request->has('project_id') && !empty($request->project_id)) {
            $query->where('idproject', $request->project_id);
        }
        
        $kodeTransaksiId = $request->idkodetransaksi ?? $request->jenis_pekerjaan;
        if (!empty($kodeTransaksiId)) {
            $query->where('idkodetransaksi', $kodeTransaksiId);
        }
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $pekerjaan = $query->orderBy('created_at', 'desc')->get();
        
        // Hitung statistik - INI YANG DIPERBAIKI
        $totalPekerjaan = $pekerjaan->count();
        $totalAnggaran = $pekerjaan->sum('anggaran');
        $ongoing = $pekerjaan->where('status', 'ongoing')->count();
        $completed = $pekerjaan->where('status', 'completed')->count();
        $planning = $pekerjaan->where('status', 'planning')->count();
        $canceled = $pekerjaan->where('status', 'canceled')->count();
        
        $projects = Project::orderBy('namaproject')->get(['id', 'namaproject']);
        
        $kodeTransaksi = Kodetransaksi::orderBy('kodetransaksi')
            ->get(['id', 'kodetransaksi', 'transaksi']);
        $jenisPekerjaan = $kodeTransaksi->mapWithKeys(function ($item) {
            return [$item->id => $item->kodetransaksi . ' - ' . $item->transaksi];
        })->all();
        
        // PASTIKAN SEMUA VARIABLE DIPASS KE VIEW
        return view('construction.report', compact(
            'pekerjaan',
            'projects',
            'kodeTransaksi',
            'jenisPekerjaan',
            'totalPekerjaan',      // ← INI HARUS ADA
            'totalAnggaran',       // ← INI HARUS ADA
            'ongoing',             // ← INI HARUS ADA
            'completed',           // ← INI HARUS ADA
            'planning',            // ← INI HARUS ADA
            'canceled',            // ← INI HARUS ADA
            'request'              // ← INI HARUS ADA
        ));
    }

    // Di Controller (PekerjaanKonstruksiController.php)
    public function show($id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with(['project', 'kodeTransaksi'])->find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $pekerjaan
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function progressIndex()
    {
        $companyId = session()->get('active_company_id');
        $projectId = session()->get('active_project_id');

        if ($projectId !== null) {
            // login project → hanya 1 project
            $projects = Project::where('id', $projectId)
                ->get(['id', 'namaproject']);

        } elseif ($companyId !== null) {
            // login company → semua project di company
            $projects = Project::where('idcompany', $companyId)
                ->orderBy('namaproject')
                ->get(['id', 'namaproject']);

        } else {
            $projects = Project::orderBy('namaproject')
                ->get(['id', 'namaproject']);
        }

        $statuses = [
            'planning' => 'Planning',
            'ongoing' => 'Sedang Berjalan',
            'completed' => 'Selesai',
            'canceled' => 'Dibatalkan'
        ];

        return view('construction.progress', compact('projects', 'statuses'));
    }

    public function getProgressData(Request $request)
    {
        try {
            $query = PekerjaanKonstruksi::with(['project', 'kodeTransaksi'])
                ->select('pekerjaan_kontruksi.*');
            // HAPUS FILTER INI: ->whereIn('status', ['ongoing', 'completed']);
            
            // Filter berdasarkan project jika ada
            if ($request->has('project_id') && !empty($request->project_id)) {
                $query->where('idproject', $request->project_id);
            }
            
            // Filter berdasarkan status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Filter berdasarkan jenis pekerjaan
            $kodeTransaksiId = $request->idkodetransaksi ?? $request->jenis_pekerjaan;
            if (!empty($kodeTransaksiId)) {
                $query->where('idkodetransaksi', $kodeTransaksiId);
            }
            
            $data = $query->orderBy('created_at', 'desc')->get();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('project', function($row) {
                    return $row->project ? $row->project->namaproject : '-';
                })
                ->addColumn('kode_transaksi_formatted', function($row) {
                    if ($row->kodeTransaksi) {
                        return '<span class="badge bg-info text-dark">'
                            . e($row->kodeTransaksi->kodetransaksi)
                            . '</span><br><small>'
                            . e($row->kodeTransaksi->transaksi)
                            . '</small>';
                    }

                    return '<span class="badge bg-secondary">-</span>';
                })
                ->addColumn('status_formatted', function($row) {
                    $status = [
                        'planning' => 'bg-secondary',
                        'ongoing' => 'bg-warning',
                        'completed' => 'bg-success',
                        'canceled' => 'bg-danger'
                    ];
                    return '<span class="badge '.($status[$row->status] ?? 'bg-light').'">'
                        .ucfirst($row->status).'</span>';
                })
                ->addColumn('progress_value', function($row) {
                    return $row->getProgressAttribute();
                })
                ->addColumn('progress_bar', function($row) {
                    $progress = $row->getProgressAttribute();
                    $progressClass = '';
                    
                    // Tentukan class berdasarkan status
                    if ($row->status === 'completed') {
                        $progressClass = 'bg-success';
                        $progress = 100; // Pastikan 100% untuk completed
                    } elseif ($row->status === 'ongoing') {
                        $progressClass = 'bg-warning progress-bar-striped progress-bar-animated';
                    } elseif ($row->status === 'planning') {
                        $progressClass = 'bg-secondary';
                        $progress = 0; // Planning selalu 0%
                    } else {
                        $progressClass = 'bg-light';
                    }
                    
                    return '<div class="progress" style="height: 25px;">
                        <div class="progress-bar '.$progressClass.'" role="progressbar" 
                            style="width: '.$progress.'%" 
                            aria-valuenow="'.$progress.'" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                            '.round($progress, 0).'%
                        </div>
                    </div>';
                })
                ->addColumn('time_remaining', function($row) {
                    if ($row->status === 'completed') {
                        return '<span class="badge bg-success">Selesai</span>';
                    } elseif ($row->status === 'ongoing' && $row->tanggal_mulai && $row->tanggal_selesai) {
                        $remaining = $row->getEstimatedTimeRemaining();
                        return '<span class="badge bg-info">'.$remaining.'</span>';
                    } elseif ($row->status === 'planning') {
                        return '<span class="badge bg-secondary">Belum Dimulai</span>';
                    }
                    return '-';
                })
                ->addColumn('anggaran_formatted', function($row) {
                    return 'Rp ' . number_format($row->anggaran ?? 0, 0, ',', '.');
                })
                ->addColumn('durasi', function($row) {
                    if ($row->tanggal_mulai && $row->tanggal_selesai) {
                        $start = date('d/m/Y', strtotime($row->tanggal_mulai));
                        $end = date('d/m/Y', strtotime($row->tanggal_selesai));
                        return $start . ' - ' . $end;
                    }
                    return '-';
                })
                ->addColumn('action', function($row) {
                    $buttons = '
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info viewPekerjaan" data-id="'.$row->id.'">
                                <i class="bi bi-eye"></i>
                            </button>';
                    
                    // Tombol update hanya untuk status ongoing
                    if ($row->status === 'ongoing') {
                        $buttons .= '
                            <button class="btn btn-sm btn-primary updateProgress" data-id="'.$row->id.'">
                                <i class="bi bi-arrow-up-circle"></i> Update
                            </button>';
                    } elseif ($row->status === 'planning') {
                        // Tombol mulai untuk planning
                        $buttons .= '
                            <button class="btn btn-sm btn-success startProgress" data-id="'.$row->id.'">
                                <i class="bi bi-play-circle"></i> Mulai
                            </button>';
                    } elseif ($row->status === 'completed') {
                        $buttons .= '
                            <button class="btn btn-sm btn-secondary" disabled>
                                <i class="bi bi-check-circle"></i> Selesai
                            </button>';
                    }
                    
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['kode_transaksi_formatted', 'status_formatted', 'progress_bar', 'time_remaining', 'action'])
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error('Error in getProgressData: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan'], 500);
        }
    }

    public function getProgressDetail($id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::with(['project', 'kodeTransaksi'])->find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $pekerjaan,
                'progress' => $pekerjaan->getProgressAttribute(),
                'time_remaining' => $pekerjaan->getEstimatedTimeRemaining()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getProgressDetail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function updateProgress(Request $request, $id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            $validated = $request->validate([
                'progress' => 'required|numeric|min:0|max:100',
                'status' => 'required|in:planning,ongoing,completed,canceled',
                'realisasi_anggaran' => 'nullable|numeric|min:0',
                'keterangan_progress' => 'nullable|string|max:500',
                'tanggal_update' => 'required|date'
            ]);
            
            DB::beginTransaction();
            
            // Update progress dan status
            $pekerjaan->status = $validated['status'];
            
            // Jika progress 100%, otomatis status completed
            if ($validated['progress'] >= 100) {
                $pekerjaan->status = 'completed';
                $pekerjaan->tanggal_selesai = $validated['tanggal_update'];
            }
            
            // Simpan realisasi anggaran jika ada
            if (isset($validated['realisasi_anggaran'])) {
                $pekerjaan->realisasi_anggaran = $validated['realisasi_anggaran'];
            }
            
            $pekerjaan->save();
            
            // Buat catatan progress
            DB::table('pekerjaan_progress_logs')->insert([
                'pekerjaan_id' => $id,
                'progress' => $validated['progress'],
                'status' => $validated['status'],
                'realisasi_anggaran' => $validated['realisasi_anggaran'] ?? null,
                'keterangan' => $validated['keterangan_progress'] ?? null,
                'tanggal_update' => $validated['tanggal_update'],
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            \Log::info('Progress pekerjaan diupdate: ' . $pekerjaan->nama_pekerjaan . ' Progress: ' . $validated['progress'] . '%');
            
            return response()->json([
                'success' => true,
                'message' => 'Progress pekerjaan berhasil diupdate'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in updateProgress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProgressLogs($id)
    {
        try {
            $logs = DB::table('pekerjaan_progress_logs')
                ->where('pekerjaan_id', $id)
                ->orderBy('tanggal_update', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $logs
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getProgressLogs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan'
            ], 500);
        }
    }

    public function startProgress(Request $request, $id)
    {
        try {
            $pekerjaan = PekerjaanKonstruksi::find($id);
            
            if (!$pekerjaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pekerjaan konstruksi tidak ditemukan'
                ], 404);
            }
            
            // Validasi: hanya bisa start dari planning
            if ($pekerjaan->status !== 'planning') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya pekerjaan dengan status planning yang bisa dimulai'
                ], 400);
            }
            
            // Validasi: harus ada tanggal mulai
            if (!$pekerjaan->tanggal_mulai) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal mulai harus diisi sebelum memulai pekerjaan'
                ], 400);
            }
            
            DB::beginTransaction();
            
            // Update status menjadi ongoing
            $pekerjaan->status = 'ongoing';
            $pekerjaan->save();
            
            // Buat log pertama
            DB::table('pekerjaan_progress_logs')->insert([
                'pekerjaan_id' => $id,
                'progress' => 0,
                'status' => 'ongoing',
                'keterangan' => 'Pekerjaan dimulai',
                'tanggal_update' => date('Y-m-d'),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            \Log::info('Pekerjaan dimulai: ' . $pekerjaan->nama_pekerjaan);
            
            return response()->json([
                'success' => true,
                'message' => 'Pekerjaan berhasil dimulai'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in startProgress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
