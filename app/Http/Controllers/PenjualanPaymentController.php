<?php
namespace App\Http\Controllers;

use App\Services\ImageCompressionService;
use App\Models\PenjualanPayment;
use App\Models\Penjualan;
use App\Models\UnitDetail;
use App\Models\Unit;
use App\Models\Customer;
use App\Models\Rekening;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class PenjualanPaymentController extends Controller
{
    private function getRealizedPaymentTotal(Penjualan $penjualan, ?int $excludePaymentId = null): float
    {
        if ($penjualan->relationLoaded('payments')) {
            return (float) $penjualan->payments
                ->where('status_payment', 'realized')
                ->when($excludePaymentId, fn ($payments) => $payments->where('id', '!=', $excludePaymentId))
                ->sum('nominal');
        }

        return (float) $penjualan->payments()
            ->where('status_payment', 'realized')
            ->when($excludePaymentId, fn ($query) => $query->where('id', '!=', $excludePaymentId))
            ->sum('nominal');
    }

    private function getSisaBelumDibayar(Penjualan $penjualan, ?int $excludePaymentId = null): float
    {
        return max(0, (float) $penjualan->harga_jual - $this->getRealizedPaymentTotal($penjualan, $excludePaymentId));
    }

    private function syncPenjualanPaymentState(Penjualan $penjualan, ?int $excludePaymentId = null): void
    {
        $sisaBelumDibayar = $this->getSisaBelumDibayar($penjualan, $excludePaymentId);

        $penjualan->update([
            'sisa_pembayaran' => $sisaBelumDibayar,
            'status_penjualan' => $sisaBelumDibayar <= 0 ? 'lunas' : 'process',
        ]);
    }

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
            // Filter by project yang aktif
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
                    
                    $totalPayment = $this->getRealizedPaymentTotal($row->penjualan);
                    $sisaBelumDibayar = $this->getSisaBelumDibayar($row->penjualan);
                    
                    return '<div>
                        <small>Harga: <strong>Rp ' . number_format($row->penjualan->harga_jual, 0, ',', '.') . '</strong></small><br>
                        <small>DP Uang muka: Rp ' . number_format($row->penjualan->dp_awal, 0, ',', '.') . '</small><br>
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
                    
                    $totalPayment = $this->getRealizedPaymentTotal($row->penjualan);
                    $sisaBelumDibayar = $this->getSisaBelumDibayar($row->penjualan);
                    
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
                    $sisaBelumDibayar = $this->getSisaBelumDibayar($row->penjualan);
                    
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
            // Filter by project yang aktif
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
        
        $totalPayment = $this->getRealizedPaymentTotal($penjualan);
        $sisaBelumDibayar = $this->getSisaBelumDibayar($penjualan);
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
        $totalPayment = $this->getRealizedPaymentTotal($penjualan);
        $sisaBelumDibayar = $this->getSisaBelumDibayar($penjualan);
        $progress = $penjualan->harga_jual > 0 ? ($totalPayment / $penjualan->harga_jual) * 100 : 0;
        
        // Cek apakah sudah lunas
        if ($sisaBelumDibayar <= 0) {
            return redirect()->route('penjualan-payment.index')
                ->with('error', 'Penjualan ini sudah lunas');
        }
        
        // Ambil daftar rekening untuk project aktif
        $rekenings = Rekening::forProject(session('active_project_id'))->get();
        
        return view('penjualan-payment.create', compact(
            'penjualan', 
            'totalPayment', 
            'sisaBelumDibayar', 
            'progress',
            'rekenings'
        ));
    }
    
    // Store pembayaran baru
    public function store(Request $request)
    {
        $request->validate([
            'penjualan_id' => 'required|exists:penjualans,id',
            'jenis_payment' => 'required|in:dp_awal,dp_uang_muka,termin_1,termin_2,termin_3,retensi,sbum,pencairan,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_payment' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'metode_pembayaran' => 'required|in:cash,transfer',
            'idrek' => 'required|exists:rekening,idrek', // Ganti dari 'bank' menjadi 'idrek'
            'no_rekening' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();
            
            $penjualan = Penjualan::with('payments')->findOrFail($request->penjualan_id);
            
            // Cek sisa yang belum dibayar
            $sisaBelumDibayar = $this->getSisaBelumDibayar($penjualan);
            
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
                $storedPath = app(ImageCompressionService::class)->storeUploadedFile($file, 'bukti_payment', 'public', $filename);
                $buktiPayment = basename($storedPath);
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
            
            // Ambil data rekening untuk mendapatkan info bank
            $rekening = Rekening::findOrFail($request->idrek);
            
            // Create payment
            $payment = PenjualanPayment::create([
                'kode_payment' => $kodePayment,
                'penjualan_id' => $penjualan->id,
                'jenis_payment' => $jenisPayment,
                'termin_ke' => $terminKe,
                'tanggal_payment' => $request->tanggal_payment,
                'nominal' => $request->nominal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'idrek' => $request->idrek, // Simpan ID rekening
                'bank' => $rekening->namabank ?? $rekening->nama, // Ambil nama bank dari rekening
                'no_rekening' => $request->no_rekening ?? $rekening->norek, // Gunakan norek dari rekening jika tidak diisi manual
                'nama_rekening' => $request->nama_rekening ?? $rekening->namarek, // Gunakan namarek dari rekening jika tidak diisi manual
                'status_payment' => 'realized', // Langsung realized untuk pembayaran penjualan
                'keterangan' => $request->keterangan,
                'bukti_payment' => $buktiPayment,
                'created_by' => Auth::id()
            ]);
            
            $penjualan->unsetRelation('payments');
            $this->syncPenjualanPaymentState($penjualan);
            
            // TAMBAHKAN KE REKENING SALDO (CASHFLOW)
            // Pembayaran penjualan dianggap sebagai pemasukan (in)
            $rekening = Rekening::findOrFail($request->idrek);
            $saldoAwal = $rekening->saldo;
            
            // Tambah saldo rekening (pemasukan)
            $rekening->saldo += $request->nominal;
            $rekening->save();
            
            // Catat di cashflows
            Cashflow::create([
                'idrek' => $request->idrek,
                'idnota' => null, // Bisa diisi nanti jika ada integrasi dengan nota
                'tanggal' => $request->tanggal_payment,
                'cashflow' => 'in', // Pemasukan
                'nominal' => $request->nominal,
                'saldo_awal' => $saldoAwal,
                'saldo_akhir' => $rekening->saldo,
                'keterangan' => "Pembayaran " . match ($jenisPayment) {
                    'dp_awal' => 'DP Awal',
                    'dp_uang_muka' => 'DP Uang Muka',
                    'retensi' => 'Retensi',
                    'sbum' => 'SBUM',
                    'pencairan' => 'Pencairan',
                    'lunas' => 'Pelunasan',
                    default => ucfirst(str_replace('_', ' ', $jenisPayment)),
                } .
                                " - Penjualan: {$penjualan->kode_penjualan} - Unit: " . 
                                ($penjualan->unitDetail->unit->namaunit ?? '') . 
                                " - Customer: " . ($penjualan->customer->nama_lengkap ?? '')
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dicatat dan saldo rekening telah ditambahkan',
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
        $payment = PenjualanPayment::with(['penjualan.unitDetail.unit.project', 'penjualan.customer', 'penjualan.payments'])
            ->findOrFail($id);
        
        // Ambil daftar rekening untuk project aktif
        $rekenings = Rekening::forProject(session('active_project_id'))->get();
            
        return view('penjualan-payment.edit', compact('payment', 'rekenings'));
    }

    // Update pembayaran
    public function update(Request $request, $id)
    {
        $payment = PenjualanPayment::with('penjualan')->findOrFail($id);
        
        $request->validate([
            'jenis_payment' => 'required|in:dp_awal,dp_uang_muka,termin_1,termin_2,termin_3,retensi,sbum,pencairan,lunas,lainnya',
            'termin_ke' => 'nullable|integer|min:1',
            'tanggal_payment' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'metode_pembayaran' => 'required|in:cash,transfer',
            'idrek' => 'required|exists:rekening,idrek', // Ganti dari 'bank' menjadi 'idrek'
            'no_rekening' => 'nullable|string|max:50',
            'nama_rekening' => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:500',
            'bukti_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8048'
        ]);
        
        try {
            DB::beginTransaction();

            $sisaBelumDibayar = $this->getSisaBelumDibayar($payment->penjualan, $payment->id);
            if ($request->nominal > $sisaBelumDibayar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nominal pembayaran (Rp ' . number_format($request->nominal, 0, ',', '.') .
                                ') melebihi sisa yang belum dibayar (Rp ' . number_format($sisaBelumDibayar, 0, ',', '.') . ')'
                ], 422);
            }
            
            // Hitung selisih nominal
            $nominalDifference = $request->nominal - $payment->nominal;
            $rekeningChanged = ($request->idrek != $payment->idrek);
            
            // ROLLBACK LOGIC - Kembalikan saldo ke kondisi sebelum transaksi
            if ($payment->status_payment == 'realized') {
                $rekeningLama = Rekening::find($payment->idrek);
                if ($rekeningLama) {
                    $saldoAwalLama = $rekeningLama->saldo;
                    // Kembalikan saldo (kurangi karena ini pemasukan)
                    $rekeningLama->saldo -= $payment->nominal;
                    $rekeningLama->save();
                    
                    \Log::info('Rollback saldo lama:', [
                        'rekening_id' => $rekeningLama->idrek,
                        'saldo_awal' => $saldoAwalLama,
                        'saldo_akhir' => $rekeningLama->saldo,
                        'nominal' => $payment->nominal
                    ]);
                }
                
                // Hapus cashflow lama jika ada
                Cashflow::where('idnota', null)
                    ->where('keterangan', 'like', "%Pembayaran%{$payment->penjualan->kode_penjualan}%")
                    ->delete();
            }
            
            // Update payment
            if ($request->hasFile('bukti_payment')) {
                // Hapus bukti lama jika ada
                if ($payment->bukti_payment) {
                    Storage::delete('public/bukti_payment/' . $payment->bukti_payment);
                }
                
                $file = $request->file('bukti_payment');
                $filename = 'bukti_payment_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $storedPath = app(ImageCompressionService::class)->storeUploadedFile($file, 'bukti_payment', 'public', $filename);
                $payment->bukti_payment = basename($storedPath);
            }
            
            // Ambil data rekening baru
            $rekeningBaru = Rekening::findOrFail($request->idrek);
            
            $payment->update([
                'jenis_payment' => $request->jenis_payment,
                'termin_ke' => $request->termin_ke,
                'tanggal_payment' => $request->tanggal_payment,
                'nominal' => $request->nominal,
                'metode_pembayaran' => $request->metode_pembayaran,
                'idrek' => $request->idrek,
                'bank' => $rekeningBaru->namabank ?? $rekeningBaru->nama,
                'no_rekening' => $request->no_rekening ?? $rekeningBaru->norek,
                'nama_rekening' => $request->nama_rekening ?? $rekeningBaru->namarek,
                'keterangan' => $request->keterangan
            ]);
            
            // Update penjualan
            $penjualan = $payment->penjualan;
            
            $this->syncPenjualanPaymentState($penjualan);
            
            // PROSES PEMBAYARAN BARU - Tambah ke rekening baru
            $rekeningBaru = Rekening::findOrFail($request->idrek);
            $saldoAwalBaru = $rekeningBaru->saldo;
            
            // Tambah saldo rekening baru
            $rekeningBaru->saldo += $request->nominal;
            $rekeningBaru->save();
            
            // Catat cashflow baru
            Cashflow::create([
                'idrek' => $request->idrek,
                'idnota' => null,
                'tanggal' => $request->tanggal_payment,
                'cashflow' => 'in',
                'nominal' => $request->nominal,
                'saldo_awal' => $saldoAwalBaru,
                'saldo_akhir' => $rekeningBaru->saldo,
                'keterangan' => "Pembayaran " . match ($request->jenis_payment) {
                    'dp_awal' => 'DP Awal',
                    'dp_uang_muka' => 'DP Uang Muka',
                    'retensi' => 'Retensi',
                    'sbum' => 'SBUM',
                    'pencairan' => 'Pencairan',
                    'lunas' => 'Pelunasan',
                    default => ucfirst(str_replace('_', ' ', $request->jenis_payment)),
                } .
                                " - Penjualan: {$penjualan->kode_penjualan} - Unit: " . 
                                ($penjualan->unitDetail->unit->namaunit ?? '') . 
                                " - Customer: " . ($penjualan->customer->nama_lengkap ?? '')
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diperbarui dan saldo rekening telah disesuaikan',
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
            $penjualan = $payment->penjualan;
            
            // ROLLBACK SALDO - Kurangi saldo rekening (karena ini pembayaran masuk)
            if ($payment->status_payment == 'realized') {
                $rekening = Rekening::find($payment->idrek);
                if ($rekening) {
                    $saldoAwal = $rekening->saldo;
                    $rekening->saldo -= $payment->nominal;
                    $rekening->save();
                    
                    \Log::info('Rollback saldo karena delete:', [
                        'rekening_id' => $rekening->idrek,
                        'saldo_awal' => $saldoAwal,
                        'saldo_akhir' => $rekening->saldo,
                        'nominal' => $payment->nominal
                    ]);
                }
                
                // Hapus cashflow terkait
                Cashflow::where('idnota', null)
                    ->where('keterangan', 'like', "%Pembayaran%{$payment->penjualan->kode_penjualan}%")
                    ->delete();
                
            }
            
            // Hapus bukti jika ada
            if ($payment->bukti_payment) {
                Storage::delete('public/bukti_payment/' . $payment->bukti_payment);
            }
            
            $payment->delete();
            $this->syncPenjualanPaymentState($penjualan, $payment->id);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dihapus dan saldo rekening telah dikembalikan'
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
