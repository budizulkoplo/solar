<?php
namespace App\Http\Controllers;

use App\Models\PenjualanPayment; // Ganti dari PencairanBank
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

class PenjualanPaymentController extends Controller
{

    public function index(Request $request)
    {
        $projectId = session('active_project_id');
        
        // Debug: Cek apakah projectId ada
        if (!$projectId) {
            return response()->json(['error' => 'Project belum dipilih'], 400);
        }
        
        if ($request->ajax()) {
            $statusFilter = $request->get('status_filter', 'all');
            $paymentMethodFilter = $request->get('payment_method_filter', 'all');
            $bankFilter = $request->get('bank_filter', '');
            $search = $request->get('search', '');
            
            // Query unit detail yang sudah terjual
            $query = UnitDetail::with([
                'unit:id,namaunit,blok,tipe,idproject',
                'unit.project:id,namaproject',
                'customer:id,nama_lengkap,no_hp,nik',
                'penjualan.payments' => function($q) {
                    $q->where('status_payment', 'realized');
                }
            ])
            ->where('status', 'terjual')
            ->whereHas('penjualan', function($q) use ($paymentMethodFilter) {
                if ($paymentMethodFilter !== 'all') {
                    $q->where('metode_pembayaran', $paymentMethodFilter);
                }
                $q->whereIn('status_penjualan', ['process', 'selesai', 'lunas']);
            })
            // Filter by project yang aktif - PERBAIKAN INI
            ->whereHas('unit', function($query) use ($projectId) {
                $query->where('idproject', $projectId);
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
                    
                    $paymentMethod = $row->penjualan->metode_pembayaran;
                    $paymentMethodBadge = $paymentMethod == 'cash' ? 
                        '<span class="badge bg-success">Cash</span>' : 
                        '<span class="badge bg-info">Kredit - ' . ($row->penjualan->bank_kredit ?? '-') . '</span>';
                    
                    return '<div>
                        <small>Kode: <strong>' . $row->penjualan->kode_penjualan . '</strong></small><br>
                        <small>Metode: ' . $paymentMethodBadge . '</small><br>
                        <small>Akad: ' . ($row->penjualan->tanggal_akad ? Carbon::parse($row->penjualan->tanggal_akad)->format('d/m/Y') : '-') . '</small>
                    </div>';
                })
                ->addColumn('financial_info', function($row) {
                    if (!$row->penjualan) return '-';
                    
                    $totalPayment = $row->penjualan->payments->where('status_payment', 'realized')->sum('nominal');
                    // BENAR: sisa_pembayaran sudah merupakan sisa setelah semua pembayaran
                    $sisaBelumDibayar = $row->penjualan->sisa_pembayaran; // Langsung pakai field ini
                    
                    return '<div>
                        <small>Harga: <strong>Rp ' . number_format($row->penjualan->harga_jual, 0, ',', '.') . '</strong></small><br>
                        <small>DP Awal: Rp ' . number_format($row->penjualan->dp_awal, 0, ',', '.') . '</small><br>
                        <small>Dibayar: <span class="text-success">Rp ' . number_format($totalPayment, 0, ',', '.') . '</span></small><br>
                        <small>Sisa: <span class="text-danger">Rp ' . number_format($sisaBelumDibayar, 0, ',', '.') . '</span></small>
                    </div>';
                })
                ->addColumn('progress_info', function($row) {
                    if (!$row->penjualan) return '<div>-</div>';
                    
                    $totalPayment = $row->penjualan->payments->where('status_payment', 'realized')->sum('nominal');
                    $progress = $row->penjualan->harga_jual > 0 ? ($totalPayment / $row->penjualan->harga_jual) * 100 : 0;
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
                        <small class="text-muted">' . $row->penjualan->payments->count() . 'x pembayaran</small>
                    </div>';
                })
                ->addColumn('status_info', function($row) {
                    if (!$row->penjualan) return '<span class="badge bg-secondary">Tidak ada data</span>';
                    
                    $totalPayment = $row->penjualan->payments->where('status_payment', 'realized')->sum('nominal');
                    // BENAR: sisa_pembayaran sudah merupakan sisa setelah semua pembayaran
                    $sisaBelumDibayar = $row->penjualan->sisa_pembayaran;
                    
                    if ($totalPayment == 0) {
                        return '<span class="badge bg-secondary">Belum Bayar</span>';
                    } elseif ($sisaBelumDibayar <= 0) {
                        return '<span class="badge bg-success">Lunas</span>';
                    } else {
                        return '<span class="badge bg-warning">Dalam Proses</span>';
                    }
                })
                ->addColumn('action', function($row) {
                    if (!$row->penjualan) return '<div class="btn-group btn-group-sm">
                        <button class="btn btn-secondary btn-sm" disabled>N/A</button>
                    </div>';
                    
                    $btn = '<div class="btn-group btn-group-sm" role="group">';
                    
                    // Tombol untuk melihat detail dan riwayat pembayaran
                    $btn .= '<a href="' . route('penjualan-payment.detail', $row->penjualan->id) . '" 
                            class="btn btn-info btn-action" title="Detail Pembayaran">
                                <i class="bi bi-list"></i>
                            </a>';
                    
                    // Hitung sisa yang belum dibayar
                    // BENAR: sisa_pembayaran sudah merupakan sisa setelah semua pembayaran
                    $sisaBelumDibayar = $row->penjualan->sisa_pembayaran;
                    
                    // Tombol untuk menambah pembayaran baru (jika masih ada sisa)
                    if ($sisaBelumDibayar > 0) {
                        $btn .= '<a href="' . route('penjualan-payment.create-by-penjualan', $row->penjualan->id) . '" 
                                class="btn btn-success btn-action" title="Tambah Pembayaran">
                                    <i class="bi bi-plus-circle"></i>
                                </a>';
                    }
                    
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['unit_info', 'customer_info', 'penjualan_info', 'financial_info', 'progress_info', 'status_info', 'action'])
                ->toJson();
        }
        
        // Hitung statistik semua penjualan untuk project yang aktif
        $query = Penjualan::with(['payments' => function($q) {
                $q->where('status_payment', 'realized');
            }])
            ->whereIn('status_penjualan', ['process', 'selesai', 'lunas'])
            // Filter by project yang aktif - PERBAIKAN INI JUGA
            ->whereHas('unitDetail.unit', function($q) use ($projectId) {
                $q->where('idproject', $projectId);
            });
        
        $penjualans = $query->get();
        
        $totalPenjualan = $penjualans->count();
        $totalPayment = 0;
        $totalNilai = $penjualans->sum('harga_jual');
        $cashCount = $penjualans->where('metode_pembayaran', 'cash')->count();
        $creditCount = $penjualans->where('metode_pembayaran', 'kredit')->count();
        
        foreach ($penjualans as $penjualan) {
            $totalPayment += $penjualan->payments->sum('nominal');
        }
        
        $persentasePayment = $totalNilai > 0 ? ($totalPayment / $totalNilai) * 100 : 0;
        
        return view('penjualan-payment.index', compact(
            'totalPenjualan', 
            'totalPayment', 
            'totalNilai', 
            'persentasePayment',
            'cashCount',
            'creditCount'
        ));
    }
    
    // Detail pembayaran untuk suatu penjualan
    public function detail($penjualanId)
    {
        $penjualan = Penjualan::with([
                'unitDetail.unit.project',
                'unitDetail.unit',
                'unitDetail.customer',
                'payments' => function($q) {
                    $q->orderBy('tanggal_payment', 'asc');
                },
                'payments.creator'
            ])
            ->whereIn('status_penjualan', ['process', 'selesai', 'lunas'])
            ->findOrFail($penjualanId);
        
        $totalPayment = $penjualan->payments->where('status_payment', 'realized')->sum('nominal');
        // $sisaBelumDibayar = $penjualan->sisa_pembayaran - $totalPayment;
        $sisaBelumDibayar =  $penjualan->sisa_pembayaran - $penjualan->dp_awal;
        $progress = $penjualan->harga_jual > 0 ? ($totalPayment / $penjualan->harga_jual) * 100 : 0;
        
        return view('penjualan-payment.detail', compact('penjualan', 'totalPayment', 'sisaBelumDibayar', 'progress'));
    }
    
    // Create pembayaran untuk penjualan tertentu
    public function createByPenjualan($penjualanId)
    {
        $penjualan = Penjualan::with([
                'unitDetail.unit.project',
                'unitDetail.unit',
                'unitDetail.customer',
                'payments' => function($q) {
                    $q->where('status_payment', 'realized')
                      ->orderBy('tanggal_payment', 'asc');
                }
            ])
            ->whereIn('status_penjualan', ['process', 'selesai', 'lunas'])
            ->findOrFail($penjualanId);
        
        // Hitung total yang sudah dibayar
        $totalPayment = $penjualan->payments->sum('nominal');
        $sisaBelumDibayar = $penjualan->sisa_pembayaran - $totalPayment;
        $progress = $penjualan->harga_jual > 0 ? ($totalPayment / $penjualan->harga_jual) * 100 : 0;
        
        // Cek apakah sudah lunas
        if ($sisaBelumDibayar <= 0) {
            return redirect()->route('penjualan-payment.index')
                ->with('error', 'Penjualan ini sudah lunas');
        }
        
        return view('penjualan-payment.create', compact(
            'penjualan', 
            'totalPayment', 
            'sisaBelumDibayar', 
            'progress'
        ));
    }
    
    // Store pembayaran baru
    public function store(Request $request)
    {
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'jenis_payment' => 'required|in:dp_awal,termin_1,termin_2,termin_3,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_payment' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'metode_pembayaran' => 'required|in:cash,transfer',
            'bank' => 'nullable|required_if:metode_pembayaran,transfer|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();
            
            $penjualan = Penjualan::with('payments')->findOrFail($request->penjualan_id);
            
            // Cek sisa yang belum dibayar
            $totalPayment = $penjualan->payments->where('status_payment', 'realized')->sum('nominal');
            $sisaBelumDibayar = $penjualan->sisa_pembayaran - $totalPayment;
            
            if ($request->nominal > $sisaBelumDibayar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pembayaran (Rp ' . number_format($request->nominal, 0, ',', '.') . 
                                ') melebihi sisa yang belum dibayar (Rp ' . number_format($sisaBelumDibayar, 0, ',', '.') . ')'
                ], 422);
            }
            
            // Generate kode payment
            $lastPayment = PenjualanPayment::orderBy('id', 'desc')->first();
            $nextNumber = $lastPayment ? intval(substr($lastPayment->kode_payment, 3)) + 1 : 1;
            $kodePayment = 'PAY' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            
            // Handle upload bukti
            $buktiPayment = null;
            if ($request->hasFile('bukti_payment')) {
                $file = $request->file('bukti_payment');
                $filename = 'bukti_payment_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public/bukti_payment', $filename);
                $buktiPayment = $filename;
            }
            
            // Auto determine jenis_payment jika tidak dipilih
            $jenisPayment = $request->jenis_payment;
            if ($jenisPayment == 'dp_awal') {
                // Cek apakah sudah ada DP awal
                $existingDp = $penjualan->payments()->where('jenis_payment', 'dp_awal')->count();
                if ($existingDp > 0) {
                    $jenisPayment = 'termin_1';
                }
            }
            
            // Auto determine termin_ke
            $terminKe = $request->termin_ke;
            if (!$terminKe && in_array($jenisPayment, ['termin_1', 'termin_2', 'termin_3'])) {
                $lastTermin = $penjualan->payments()
                    ->whereIn('jenis_payment', ['termin_1', 'termin_2', 'termin_3'])
                    ->max('termin_ke');
                $terminKe = $lastTermin ? $lastTermin + 1 : 1;
            }
            
            // Create payment
            $payment = PenjualanPayment::create([
                'kode_payment' => $kodePayment,
                'penjualan_id' => $penjualan->id,
                'jenis_payment' => $jenisPayment,
                'termin_ke' => $terminKe,
                'tanggal_payment' => $request->tanggal_payment,
                'nominal' => $request->nominal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank' => $request->bank,
                'no_rekening' => $request->no_rekening,
                'nama_rekening' => $request->nama_rekening,
                'status_payment' => 'realized', // Untuk cash langsung realized
                'keterangan' => $request->keterangan,
                'bukti_payment' => $buktiPayment,
                'created_by' => Auth::id()
            ]);
            
            // Untuk pembayaran cash, langsung update penjualan
            if ($request->metode_pembayaran == 'cash') {
                // Hitung total pembayaran setelah ini
                $totalPaymentAfter = $totalPayment + $request->nominal;
                
                // Update penjualan
                $penjualan->update([
                    'sisa_pembayaran' => max(0, $penjualan->sisa_pembayaran - $request->nominal)
                ]);
                
                // Cek jika sudah lunas
                if ($totalPaymentAfter >= $penjualan->harga_jual) {
                    $penjualan->update([
                        'status_penjualan' => 'lunas',
                        'sisa_pembayaran' => 0
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dicatat',
                'redirect' => route('penjualan-payment.detail', $penjualan->id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Edit pembayaran
    public function edit($id)
    {
        $payment = PenjualanPayment::with(['penjualan.unitDetail.unit.project', 'penjualan.customer'])
            ->findOrFail($id);
            
        return view('penjualan-payment.edit', compact('payment'));
    }

    // Update pembayaran
    public function update(Request $request, $id)
    {
        $payment = PenjualanPayment::with('penjualan')->findOrFail($id);
        
        $request->validate([
            'jenis_payment' => 'required|in:dp_awal,termin_1,termin_2,termin_3,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_payment' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'metode_pembayaran' => 'required|in:cash,transfer',
            'bank' => 'nullable|required_if:metode_pembayaran,transfer|string|max:100',
            'no_rekening' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Hitung selisih nominal
            $nominalDifference = $request->nominal - $payment->nominal;
            
            // Update payment
            if ($request->hasFile('bukti_payment')) {
                // Hapus bukti lama jika ada
                if ($payment->bukti_payment) {
                    Storage::delete('public/bukti_payment/' . $payment->bukti_payment);
                }
                
                $file = $request->file('bukti_payment');
                $filename = 'bukti_payment_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('public/bukti_payment', $filename);
                $payment->bukti_payment = $filename;
            }
            
            $payment->update([
                'jenis_payment' => $request->jenis_payment,
                'termin_ke' => $request->termin_ke,
                'tanggal_payment' => $request->tanggal_payment,
                'nominal' => $request->nominal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'bank' => $request->bank,
                'no_rekening' => $request->no_rekening,
                'nama_rekening' => $request->nama_rekening,
                'keterangan' => $request->keterangan
            ]);
            
            // Update penjualan jika ada perubahan nominal
            if ($nominalDifference != 0 && $payment->status_payment == 'realized') {
                $penjualan = $payment->penjualan;
                $newSisa = $penjualan->sisa_pembayaran - $nominalDifference;
                
                $penjualan->update([
                    'sisa_pembayaran' => max(0, $newSisa)
                ]);
                
                // Cek jika sudah lunas
                $totalPayment = $penjualan->payments()->where('status_payment', 'realized')->sum('nominal');
                if ($totalPayment >= $penjualan->harga_jual) {
                    $penjualan->update([
                        'status_penjualan' => 'lunas',
                        'sisa_pembayaran' => 0
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diperbarui',
                'redirect' => route('penjualan-payment.detail', $payment->penjualan_id)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Hapus pembayaran
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $payment = PenjualanPayment::with('penjualan')->findOrFail($id);
            
            // Hapus bukti jika ada
            if ($payment->bukti_payment) {
                Storage::delete('public/bukti_payment/' . $payment->bukti_payment);
            }
            
            // Update penjualan jika payment sudah realized
            if ($payment->status_payment == 'realized') {
                $penjualan = $payment->penjualan;
                $newSisa = $penjualan->sisa_pembayaran + $payment->nominal;
                
                $penjualan->update([
                    'sisa_pembayaran' => $newSisa,
                    'status_penjualan' => 'process' // Kembalikan ke process jika belum lunas
                ]);
            }
            
            $payment->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}