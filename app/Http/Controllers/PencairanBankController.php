<?php

namespace App\Http\Controllers;

use App\Models\PencairanBank;
use App\Models\Penjualan;
use App\Models\UnitDetail;
use App\Models\Unit;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PencairanBankController extends Controller
{
    // Index - Daftar Unit Terjual untuk Pencairan
    public function index(Request $request)
    {
        $projectId = session('selected_project_id');
        
        if ($request->ajax()) {
            $statusFilter = $request->get('status_filter', 'all');
            $bankFilter = $request->get('bank_filter', '');
            $search = $request->get('search', '');
            
            // Query unit detail yang sudah terjual dan memiliki data penjualan dengan metode kredit
            $query = UnitDetail::with([
                'unit:id,namaunit,blok,tipe,idproject',
                'unit.project:id,namaproject',
                'customer:id,nama_lengkap,no_hp,nik',
                'penjualan.pencairanBank' => function($q) {
                    $q->where('status_pencairan', 'realized');
                }
            ])
            ->where('status', 'terjual')
            ->whereHas('penjualan', function($q) {
                $q->where('metode_pembayaran', 'kredit')
                  ->whereIn('status_penjualan', ['process', 'selesai']);
            })
            ->when($projectId, function($q) use ($projectId) {
                $q->whereHas('unit', function($query) use ($projectId) {
                    $query->where('idproject', $projectId);
                });
            })
            ->when($bankFilter, function($q) use ($bankFilter) {
                $q->whereHas('penjualan', function($query) use ($bankFilter) {
                    $query->where('bank_kredit', $bankFilter);
                });
            })
            ->when($search, function($q) use ($search) {
                $q->where(function($query) use ($search) {
                    $query->whereHas('unit', function($q) use ($search) {
                        $q->where('namaunit', 'like', "%{$search}%")
                          ->orWhere('blok', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function($q) use ($search) {
                        $q->where('nama_lengkap', 'like', "%{$search}%");
                    })
                    ->orWhereHas('penjualan', function($q) use ($search) {
                        $q->where('kode_penjualan', 'like', "%{$search}%");
                    });
                });
            });
            
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('unit_info', function($row) {
                    if (!$row->unit) return '-';
                    
                    $blok = $row->unit->blok ? ' Blok ' . $row->unit->blok : '';
                    return '<div>
                        <strong>' . ($row->unit->namaunit ?? '-') . '</strong>' . $blok . '<br>
                        <small class="text-muted">No: ' . ($row->no_rumah ?? 'UR-' . $row->id) . '</small>
                    </div>';
                })
                ->addColumn('customer_info', function($row) {
                    if (!$row->customer) return '-';
                    
                    $customer = $row->customer;
                    return '<div>
                        <strong>' . ($customer->nama_lengkap ?? '-') . '</strong><br>
                        <small class="text-muted">' . ($customer->no_hp ?? '-') . '</small>
                    </div>';
                })
                ->addColumn('penjualan_info', function($row) {
                    if (!$row->penjualan) return '-';
                    
                    return '<div>
                        <small>Kode: <strong>' . $row->penjualan->kode_penjualan . '</strong></small><br>
                        <small>Bank: ' . $row->penjualan->bank_kredit . '</small><br>
                        <small>Akad: ' . ($row->penjualan->tanggal_akad ? Carbon::parse($row->penjualan->tanggal_akad)->format('d/m/Y') : '-') . '</small>
                    </div>';
                })
                ->addColumn('financial_info', function($row) {
                    if (!$row->penjualan) return '-';
                    
                    $totalDicairkan = $row->penjualan->pencairanBank->sum('nominal_pencairan');
                    $sisaBelumDicairkan = $row->penjualan->sisa_pembayaran - $totalDicairkan;
                    
                    return '<div>
                        <small>Harga: <strong>Rp ' . number_format($row->penjualan->harga_jual, 0, ',', '.') . '</strong></small><br>
                        
                        <small>Dicairkan: <span class="text-success">Rp ' . number_format($totalDicairkan, 0, ',', '.') . '</span></small><br>
                        <small>Sisa: <span class="text-danger">Rp ' . number_format($sisaBelumDicairkan, 0, ',', '.') . '</span></small>
                    </div>';
                })
                ->addColumn('progress_info', function($row) {
                    if (!$row->penjualan) return '<div>-</div>';
                    
                    $totalDicairkan = $row->penjualan->pencairanBank->sum('nominal_pencairan');
                    $progress = $row->penjualan->harga_jual > 0 ? ($totalDicairkan / $row->penjualan->harga_jual) * 100 : 0;
                    $color = 'bg-success';
                    if ($progress < 30) $color = 'bg-danger';
                    elseif ($progress < 70) $color = 'bg-warning';
                    
                    return '<div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>' . number_format($progress, 1) . '%</span>
                            <span>' . ($progress >= 100 ? 'LUNAS' : '') . '</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar ' . $color . '" role="progressbar" 
                                 style="width: ' . $progress . '%" 
                                 aria-valuenow="' . $progress . '" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">' . $row->penjualan->pencairanBank->count() . 'x pencairan</small>
                    </div>';
                })
                ->addColumn('status_info', function($row) {
                    if (!$row->penjualan) return '<span class="badge bg-secondary">Tidak ada data</span>';
                    
                    $totalDicairkan = $row->penjualan->pencairanBank->sum('nominal_pencairan');
                    $sisaBelumDicairkan = $row->penjualan->sisa_pembayaran - $totalDicairkan;
                    
                    if ($totalDicairkan == 0) {
                        return '<span class="badge bg-secondary">Belum Dicairkan</span>';
                    } elseif ($sisaBelumDicairkan <= 0) {
                        return '<span class="badge bg-success">Lunas Dicairkan</span>';
                    } else {
                        return '<span class="badge bg-warning">Dalam Proses</span>';
                    }
                })
                ->addColumn('action', function($row) {
                    if (!$row->penjualan) return '<div class="btn-group btn-group-sm">
                        <button class="btn btn-secondary btn-sm" disabled>N/A</button>
                    </div>';
                    
                    $btn = '<div class="btn-group btn-group-sm" role="group">';
                    
                    // Tombol untuk melihat detail dan riwayat pencairan
                    $btn .= '<a href="' . route('pencairan-bank.detail', $row->penjualan->id) . '" 
                               class="btn btn-info btn-action" title="Detail Pencairan">
                                <i class="bi bi-list"></i>
                            </a>';
                    
                    // Hitung sisa yang belum dicairkan
                    $totalDicairkan = $row->penjualan->pencairanBank->sum('nominal_pencairan');
                    $sisaBelumDicairkan = $row->penjualan->sisa_pembayaran - $totalDicairkan;
                    
                    // Tombol untuk menambah pencairan baru (jika masih ada sisa)
                    if ($sisaBelumDicairkan > 0) {
                        $btn .= '<a href="' . route('pencairan-bank.create-by-penjualan', $row->penjualan->id) . '" 
                                   class="btn btn-success btn-action" title="Tambah Pencairan">
                                    <i class="bi bi-plus-circle"></i>
                                </a>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['unit_info', 'customer_info', 'penjualan_info', 'financial_info', 'progress_info', 'status_info', 'action'])
                ->toJson();
        }
        
        // Hitung statistik berdasarkan penjualan yang memiliki metode pembayaran kredit
        $query = Penjualan::with(['pencairanBank' => function($q) {
                $q->where('status_pencairan', 'realized');
            }])
            ->where('metode_pembayaran', 'kredit')
            ->whereIn('status_penjualan', ['process', 'selesai']);
            
        if ($projectId) {
            $query->whereHas('unitDetail.unit', function($q) use ($projectId) {
                $q->where('idproject', $projectId);
            });
        }
        
        $penjualans = $query->get();
        
        $totalPenjualan = $penjualans->count();
        $totalDicairkan = 0;
        $totalNilai = $penjualans->sum('harga_jual');
        
        foreach ($penjualans as $penjualan) {
            $totalDicairkan += $penjualan->pencairanBank->sum('nominal_pencairan');
        }
        
        $persentaseDicairkan = $totalNilai > 0 ? ($totalDicairkan / $totalNilai) * 100 : 0;
        
        return view('pencairan-bank.index', compact(
            'totalPenjualan', 
            'totalDicairkan', 
            'totalNilai', 
            'persentaseDicairkan'
        ));
    }
    
    // Detail pencairan untuk suatu penjualan
    public function detail($penjualanId)
    {
        $penjualan = Penjualan::with([
                'unitDetail.unit.project',
                'unitDetail.unit',
                'unitDetail.customer',
                'pencairanBank' => function($q) {
                    $q->orderBy('tanggal_pencairan', 'asc');
                },
                'pencairanBank.creator'
            ])
            ->where('metode_pembayaran', 'kredit')
            ->whereIn('status_penjualan', ['process', 'selesai'])
            ->findOrFail($penjualanId);
        
        $totalPencairan = $penjualan->pencairanBank->where('status_pencairan', 'realized')->sum('nominal_pencairan');
        $sisaBelumDicairkan = $penjualan->sisa_pembayaran - $totalPencairan;
        $progress = $penjualan->harga_jual > 0 ? ($totalPencairan / $penjualan->harga_jual) * 100 : 0;
        
        return view('pencairan-bank.detail', compact('penjualan', 'totalPencairan', 'sisaBelumDicairkan', 'progress'));
    }
    
    // Create pencairan untuk penjualan tertentu
    public function createByPenjualan($penjualanId)
    {
        $penjualan = Penjualan::with([
                'unitDetail.unit.project',
                'unitDetail.unit',
                'unitDetail.customer',
                'pencairanBank' => function($q) {
                    $q->where('status_pencairan', 'realized')
                      ->orderBy('tanggal_pencairan', 'asc');
                }
            ])
            ->where('metode_pembayaran', 'kredit')
            ->whereIn('status_penjualan', ['process', 'selesai'])
            ->findOrFail($penjualanId);
        
        // Hitung total yang sudah dicairkan
        $totalPencairan = $penjualan->pencairanBank->sum('nominal_pencairan');
        $sisaBelumDicairkan = $penjualan->sisa_pembayaran - $totalPencairan;
        $progress = $penjualan->harga_jual > 0 ? ($totalPencairan / $penjualan->harga_jual) * 100 : 0;
        
        // Cek apakah masih ada sisa yang bisa dicairkan
        if ($sisaBelumDicairkan <= 0) {
            return redirect()->route('pencairan-bank.index')
                ->with('error', 'Penjualan ini sudah lunas dicairkan');
        }
        
        return view('pencairan-bank.create', compact(
            'penjualan', 
            'totalPencairan', 
            'sisaBelumDicairkan', 
            'progress'
        ));
    }
    
    // Store pencairan baru
    public function store(Request $request)
    {
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'jenis_pencairan' => 'required|in:dp_awal,termin_1,termin_2,termin_3,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_pencairan' => 'required|date',
            'nominal_pencairan' => 'required|numeric|min:1000',
            'bank_kredit' => 'required|string|max:100',
            'no_rekening_bank' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_pencairan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();
            
            $penjualan = Penjualan::with('pencairanBank')->findOrFail($request->penjualan_id);
            
            // Cek sisa yang belum dicairkan
            $totalPencairan = $penjualan->pencairanBank->where('status_pencairan', 'realized')->sum('nominal_pencairan');
            $sisaBelumDicairkan = $penjualan->sisa_pembayaran - $totalPencairan;
            
            if ($request->nominal_pencairan > $sisaBelumDicairkan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pencairan (Rp ' . number_format($request->nominal_pencairan, 0, ',', '.') . 
                                ') melebihi sisa yang belum dicairkan (Rp ' . number_format($sisaBelumDicairkan, 0, ',', '.') . ')'
                ], 422);
            }
            
            // Generate kode pencairan
            $lastPencairan = PencairanBank::orderBy('id', 'desc')->first();
            $nextNumber = $lastPencairan ? intval(substr($lastPencairan->kode_pencairan, 3)) + 1 : 1;
            $kodePencairan = 'PCB' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            
            // Handle upload bukti
            $buktiPencairan = null;
            if ($request->hasFile('bukti_pencairan')) {
                $file = $request->file('bukti_pencairan');
                $filename = 'bukti_pencairan_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public/bukti_pencairan', $filename);
                $buktiPencairan = $filename;
            }
            
            // Auto determine jenis_pencairan jika tidak dipilih
            $jenisPencairan = $request->jenis_pencairan;
            if ($jenisPencairan == 'dp_awal') {
                // Cek apakah sudah ada DP awal
                $existingDp = $penjualan->pencairanBank()->where('jenis_pencairan', 'dp_awal')->count();
                if ($existingDp > 0) {
                    $jenisPencairan = 'termin_1'; // Jika sudah ada DP, anggap sebagai termin
                }
            }
            
            // Auto determine termin_ke
            $terminKe = $request->termin_ke;
            if (!$terminKe && in_array($jenisPencairan, ['termin_1', 'termin_2', 'termin_3'])) {
                $lastTermin = $penjualan->pencairanBank()
                    ->whereIn('jenis_pencairan', ['termin_1', 'termin_2', 'termin_3'])
                    ->max('termin_ke');
                $terminKe = $lastTermin ? $lastTermin + 1 : 1;
            }
            
            // Create pencairan bank
            $pencairan = PencairanBank::create([
                'kode_pencairan' => $kodePencairan,
                'penjualan_id' => $penjualan->id,
                'bank_kredit' => $request->bank_kredit,
                'tanggal_pencairan' => $request->tanggal_pencairan,
                'nominal_pencairan' => $request->nominal_pencairan,
                'jenis_pencairan' => $jenisPencairan,
                'termin_ke' => $terminKe,
                'status_pencairan' => 'pending',
                'keterangan' => $request->keterangan,
                'bukti_pencairan' => $buktiPencairan,
                'no_rekening_bank' => $request->no_rekening_bank,
                'nama_rekening' => $request->nama_rekening,
                'created_by' => Auth::id()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil diajukan',
                'redirect' => route('pencairan-bank.detail', $penjualan->id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Approve pencairan
    public function approve($id)
    {
        try {
            DB::beginTransaction();
            
            $pencairan = PencairanBank::findOrFail($id);
            
            if ($pencairan->status_pencairan != 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pencairan tidak valid'
                ], 422);
            }
            
            $pencairan->update([
                'status_pencairan' => 'approved'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil diapprove'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Reject pencairan
    public function reject($id)
    {
        try {
            DB::beginTransaction();
            
            $pencairan = PencairanBank::findOrFail($id);
            
            if ($pencairan->status_pencairan != 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Status pencairan tidak valid'
                ], 422);
            }
            
            $pencairan->update([
                'status_pencairan' => 'rejected'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil direject'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Realisasi pencairan
    public function realisasi(Request $request, $id)
    {
        $request->validate([
            'tanggal_realisasi' => 'required|date',
            'keterangan_realisasi' => 'nullable|string|max:500'
        ]);
        
        try {
            DB::beginTransaction();
            
            $pencairan = PencairanBank::with('penjualan')->findOrFail($id);
            
            if ($pencairan->status_pencairan != 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pencairan harus dalam status approved sebelum direalisasi'
                ], 422);
            }
            
            // Update status dan tanggal realisasi
            $pencairan->update([
                'status_pencairan' => 'realized',
                'tanggal_realisasi' => $request->tanggal_realisasi,
                'keterangan' => $pencairan->keterangan . "\n\n[REALISASI] " . $request->keterangan_realisasi
            ]);
            
            // Cek apakah penjualan sudah lunas
            $totalDicairkan = $pencairan->penjualan->pencairanBank()
                ->where('status_pencairan', 'realized')
                ->sum('nominal_pencairan');
            
            if ($totalDicairkan >= $pencairan->penjualan->harga_jual) {
                // Update status penjualan jika sudah lunas
                $pencairan->penjualan->update([
                    'status_penjualan' => 'lunas',
                    'sisa_pembayaran' => 0
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil direalisasi'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Hapus pencairan
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $pencairan = PencairanBank::findOrFail($id);
            
            // Cek status
            if (!in_array($pencairan->status_pencairan, ['pending', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pencairan bank tidak dapat dihapus karena status sudah ' . $pencairan->status_pencairan
                ], 422);
            }
            
            // Hapus bukti jika ada
            if ($pencairan->bukti_pencairan) {
                Storage::delete('public/bukti_pencairan/' . $pencairan->bukti_pencairan);
            }
            
            $pencairan->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Get detail penjualan untuk AJAX
    public function getPenjualanDetail($id)
    {
        $penjualan = Penjualan::with([
                'unitDetail.unit',
                'unitDetail.customer',
                'pencairanBank' => function($q) {
                    $q->where('status_pencairan', 'realized')
                      ->orderBy('tanggal_pencairan', 'asc');
                }
            ])
            ->where('metode_pembayaran', 'kredit')
            ->whereIn('status_penjualan', ['process', 'selesai'])
            ->findOrFail($id);
        
        $totalDicairkan = $penjualan->pencairanBank->sum('nominal_pencairan');
        $sisaBelumDicairkan = $penjualan->sisa_pembayaran - $totalDicairkan;
        $progress = $penjualan->harga_jual > 0 ? ($totalDicairkan / $penjualan->harga_jual) * 100 : 0;
        
        // Riwayat pencairan
        $riwayat = [];
        foreach ($penjualan->pencairanBank as $p) {
            $riwayat[] = [
                'jenis' => $p->jenis_pencairan,
                'termin' => $p->termin_ke,
                'tanggal' => $p->tanggal_pencairan ? Carbon::parse($p->tanggal_pencairan)->format('d/m/Y') : '-',
                'nominal' => 'Rp ' . number_format($p->nominal_pencairan, 0, ',', '.'),
                'status' => $p->status_pencairan
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'unit_nama' => $penjualan->unitDetail->unit->namaunit ?? '-',
                'customer_nama' => $penjualan->unitDetail->customer->nama_lengkap ?? '-',
                'harga_jual' => 'Rp ' . number_format($penjualan->harga_jual, 0, ',', '.'),
                'dp_awal' => 'Rp ' . number_format($penjualan->dp_awal, 0, ',', '.'),
                'sisa_pembayaran' => 'Rp ' . number_format($penjualan->sisa_pembayaran, 0, ',', '.'),
                'bank_kredit' => $penjualan->bank_kredit,
                'tenor_kredit' => $penjualan->tenor_kredit,
                'cicilan_bulanan' => 'Rp ' . number_format($penjualan->cicilan_bulanan, 0, ',', '.'),
                'tanggal_akad' => $penjualan->tanggal_akad ? Carbon::parse($penjualan->tanggal_akad)->format('d/m/Y') : '-',
                'total_dicairkan' => 'Rp ' . number_format($totalDicairkan, 0, ',', '.'),
                'sisa_belum_dicairkan' => 'Rp ' . number_format($sisaBelumDicairkan, 0, ',', '.'),
                'progress' => number_format($progress, 1) . '%',
                'riwayat_pencairan' => $riwayat
            ]
        ]);
    }

    public function edit($id)
    {
        $pencairan = PencairanBank::with(['penjualan.unitDetail.unit.project', 'penjualan.customer'])
            ->findOrFail($id);
            
        // Cek status
        if ($pencairan->status_pencairan != 'pending') {
            return redirect()->route('pencairan-bank.detail', $pencairan->penjualan_id)
                ->with('error', 'Pencairan bank tidak dapat diedit karena status sudah ' . $pencairan->status_pencairan);
        }
        
        return view('pencairan-bank.edit', compact('pencairan'));
    }

    // Tambahkan method update
    public function update(Request $request, $id)
    {
        $pencairan = PencairanBank::with('penjualan')->findOrFail($id);
        
        // Cek status
        if ($pencairan->status_pencairan != 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pencairan bank tidak dapat diedit karena status sudah ' . $pencairan->status_pencairan
            ], 422);
        }
        
        $request->validate([
            'jenis_pencairan' => 'required|in:dp_awal,termin_1,termin_2,termin_3,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_pencairan' => 'required|date',
            'nominal_pencairan' => 'required|numeric|min:1000',
            'bank_kredit' => 'required|string|max:100',
            'no_rekening_bank' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_pencairan' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Cek apakah nominal melebihi sisa yang belum dicairkan (kecuali untuk pencairan ini sendiri)
            $totalSudahDicairkan = $pencairan->penjualan->pencairanBank
                ->where('status_pencairan', 'realized')
                ->where('id', '!=', $id)
                ->sum('nominal_pencairan');
                
            $sisaBelumDicairkan = $pencairan->penjualan->sisa_pembayaran - $totalSudahDicairkan;
            
            if ($request->nominal_pencairan > $sisaBelumDicairkan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pencairan (Rp ' . number_format($request->nominal_pencairan, 0, ',', '.') . 
                                ') melebihi sisa yang belum dicairkan (Rp ' . number_format($sisaBelumDicairkan, 0, ',', '.') . ')'
                ], 422);
            }
            
            // Handle upload bukti baru
            if ($request->hasFile('bukti_pencairan')) {
                // Hapus bukti lama jika ada
                if ($pencairan->bukti_pencairan) {
                    Storage::delete('public/bukti_pencairan/' . $pencairan->bukti_pencairan);
                }
                
                $file = $request->file('bukti_pencairan');
                $filename = 'bukti_pencairan_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public/bukti_pencairan', $filename);
                $pencairan->bukti_pencairan = $filename;
            }
            
            // Update data
            $pencairan->update([
                'jenis_pencairan' => $request->jenis_pencairan,
                'termin_ke' => $request->termin_ke,
                'tanggal_pencairan' => $request->tanggal_pencairan,
                'nominal_pencairan' => $request->nominal_pencairan,
                'bank_kredit' => $request->bank_kredit,
                'no_rekening_bank' => $request->no_rekening_bank,
                'nama_rekening' => $request->nama_rekening,
                'keterangan' => $request->keterangan
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pencairan bank berhasil diperbarui',
                'redirect' => route('pencairan-bank.detail', $pencairan->penjualan_id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambahkan method export (placeholder)
    public function exportExcel($penjualanId)
    {
        // Implementasi export Excel
        return response()->json(['message' => 'Export Excel belum diimplementasi']);
    }

    public function exportPDF($penjualanId)
    {
        // Implementasi export PDF
        return response()->json(['message' => 'Export PDF belum diimplementasi']);
    }
}