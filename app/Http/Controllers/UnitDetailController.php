<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDetail;
use App\Models\Project;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitDetailController extends Controller
{
    public function index(Request $request)
    {
        // Query untuk mendapatkan projects dengan units dan details
        $projects = Project::with(['units' => function($query) use ($request) {
                // Filter berdasarkan unit_id jika ada
                if ($request->has('unit_id') && !empty($request->unit_id)) {
                    $query->where('id', $request->unit_id);
                }
                $query->with(['details' => function($q) use ($request) {
                    if ($request->has('status') && !empty($request->status)) {
                        $q->where('status', $request->status);
                    }
                }, 'jenisUnit']);
            }])
            ->whereHas('units')
            ->orderBy('namaproject')
            ->get();
        
        // Get all units untuk dropdown filter
        $units = Unit::when($request->has('project_id') && $request->project_id != '', function($query) use ($request) {
                return $query->where('idproject', $request->project_id);
            })
            ->orderBy('namaunit')
            ->get(['id', 'namaunit', 'blok', 'idproject']);
        
        $selectedUnit = $request->has('unit_id') ? Unit::find($request->unit_id) : null;
        
        return view('master.units.details', compact('projects', 'units', 'selectedUnit'));
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:tersedia,booking_unit,bi_check,pemberkasan_bank,acc,tidak_acc,akad,pencairan,bast,terjual',
                'customer_data' => 'required_if:status,booking_unit|array',
                'booking_data' => 'required_if:status,booking_unit|array',
                'penjualan_data' => 'required_if:status,terjual|array'
            ]);
            
            DB::beginTransaction();
            
            $unitDetail = UnitDetail::with(['unit', 'customer', 'booking', 'penjualan'])->findOrFail($id);
            $oldStatus = $unitDetail->status;
            
            // Validasi alur status
            if (!$this->validateStatusFlow($oldStatus, $validated['status'])) {
                throw new \Exception('Tidak bisa mengubah status dari ' . $oldStatus . ' ke ' . $validated['status']);
            }
            
            // Jika status berubah ke booking, buat customer dan booking
            if ($validated['status'] === 'booking_unit' && isset($validated['customer_data'])) {
                // Cek apakah unit sudah ada customer
                if ($unitDetail->customer_id) {
                    throw new \Exception('Unit ini sudah memiliki customer');
                }
                
                // 1. Buat customer baru
                $customer = $this->createCustomer($validated['customer_data']);
                
                // 2. Buat booking
                $booking = $this->createBooking($customer->id, $unitDetail->id, $validated['booking_data']);
                
                // 3. Update unit detail dengan customer_id dan booking_id
                $unitDetail->customer_id = $customer->id;
                $unitDetail->booking_id = $booking->id;
                
            } 
            // Jika status berubah ke tidak_acc, reset ke tersedia
            elseif ($validated['status'] === 'tidak_acc') {
                // Hanya bisa dari booking_unit, bi_check, atau pemberkasan_bank
                if (!in_array($oldStatus, ['booking_unit', 'bi_check', 'pemberkasan_bank'])) {
                    throw new \Exception('Status Tidak ACC hanya bisa dari Booking, BI Check, atau Pemberkasan Bank');
                }
                
                // Update status ke tersedia (otomatis setelah tidak_acc)
                $unitDetail->status = 'tersedia';
                $unitDetail->save();
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Status berubah menjadi Tidak ACC, unit kembali ke status Tersedia',
                    'data' => [
                        'unit_detail' => $unitDetail,
                        'old_status' => $oldStatus
                    ]
                ]);
            }
            // Jika status berubah ke terjual, buat penjualan
            elseif ($validated['status'] === 'terjual' && isset($validated['penjualan_data'])) {
                // Pastikan status sebelumnya adalah bast
                if ($oldStatus !== 'bast') {
                    throw new \Exception('Unit harus dalam status BAST sebelum bisa dijual');
                }
                
                // Pastikan unit sudah di-booking dan ada customer
                if (!$unitDetail->customer_id || !$unitDetail->booking_id) {
                    throw new \Exception('Unit harus dalam status booking dengan data customer lengkap');
                }
                
                // Buat penjualan
                $penjualan = $this->createPenjualan(
                    $unitDetail->customer_id, 
                    $unitDetail->id, 
                    $unitDetail->booking_id,
                    $validated['penjualan_data']
                );
                
                // Update unit detail dengan penjualan_id
                $unitDetail->penjualan_id = $penjualan->id;
                
                // Update status booking menjadi completed
                if ($unitDetail->booking) {
                    $unitDetail->booking->update(['status_booking' => 'completed']);
                }
            }
            
            // Update status unit detail
            $unitDetail->status = $validated['status'];
            $unitDetail->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diubah dari ' . $oldStatus . ' menjadi ' . $validated['status'],
                'data' => [
                    'unit_detail' => $unitDetail,
                    'customer' => $customer ?? null,
                    'booking' => $booking ?? null,
                    'penjualan' => $penjualan ?? null
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in updateStatus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambahkan method validasi alur di controller
    private function validateStatusFlow($currentStatus, $newStatus)
    {
        $validFlows = [
            'tersedia' => ['booking_unit'],
            'booking_unit' => ['tersedia', 'bi_check', 'tidak_acc'],
            'bi_check' => ['booking_unit', 'pemberkasan_bank', 'tidak_acc'],
            'pemberkasan_bank' => ['bi_check', 'acc', 'tidak_acc'],
            'acc' => ['pemberkasan_bank', 'akad'],
            'tidak_acc' => ['tersedia'],
            'akad' => ['acc', 'pencairan'],
            'pencairan' => ['akad', 'bast'],
            'bast' => ['pencairan', 'terjual'],
            'terjual' => []
        ];
        
        // Jika sudah terjual, tidak bisa diubah
        if ($currentStatus === 'terjual') {
            return false;
        }
        
        return in_array($newStatus, $validFlows[$currentStatus] ?? []);
    }

    private function createCustomer(array $customerData)
    {
        // Validasi data customer
        $validatedCustomer = validator($customerData, [
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nik' => 'required|string|size:16|unique:customers,nik',
            'no_kk' => 'nullable|string|size:16',
            'alamat_ktp' => 'required|string',
            'rt_rw_ktp' => 'required|string|max:20',
            'kelurahan_ktp' => 'required|string|max:100',
            'kecamatan_ktp' => 'required|string|max:100',
            'kota_ktp' => 'required|string|max:100',
            'alamat_domisili' => 'nullable|string',
            'no_hp' => 'required|string|max:20',
            'email' => 'nullable|email',
            'pekerjaan' => 'required|string|max:100',
            'penghasilan_bulanan' => 'nullable|numeric',
        ])->validate();

        return Customer::create($validatedCustomer);
    }

    private function createBooking($customerId, $unitDetailId, array $bookingData)
    {
        $validatedBooking = validator($bookingData, [

            'dp_awal' => 'required|numeric|min:0',
            'metode_pembayaran_dp' => 'required|string|max:100',

            'keterangan' => 'nullable|string'
        ])->validate();

        return Booking::create(array_merge($validatedBooking, [
            'kode_booking' => $this->generateKodeBooking(),
            'customer_id' => $customerId,
            'unit_detail_id' => $unitDetailId,
            'status_booking' => 'active',
            'created_by' => auth()->id()
        ]));
    }

    private function createPenjualan($customerId, $unitDetailId, $bookingId, array $penjualanData)
    {
        $unitDetail = UnitDetail::with('unit')->find($unitDetailId);
        
        $validatedPenjualan = validator($penjualanData, [
            'harga_jual' => 'required|numeric|min:0',
            'dp_awal' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|string|in:cash,kredit',
            'bank_kredit' => 'required_if:metode_pembayaran,kredit|string|max:100',
            'tenor_kredit' => 'required_if:metode_pembayaran,kredit|integer|min:1',
            'tanggal_akad' => 'required|date',
            'keterangan' => 'nullable|string'
        ])->validate();

        // Hitung sisa pembayaran
        $sisaPembayaran = $validatedPenjualan['harga_jual'] - ($validatedPenjualan['dp_awal'] ?? 0);
        
        // Hitung cicilan bulanan jika kredit
        $cicilanBulanan = null;
        if ($validatedPenjualan['metode_pembayaran'] === 'kredit' && $validatedPenjualan['tenor_kredit'] > 0) {
            $cicilanBulanan = $sisaPembayaran / $validatedPenjualan['tenor_kredit'];
        }

        return Penjualan::create(array_merge($validatedPenjualan, [
            'kode_penjualan' => $this->generateKodePenjualan(),
            'customer_id' => $customerId,
            'unit_detail_id' => $unitDetailId,
            'booking_id' => $bookingId,
            'sisa_pembayaran' => $sisaPembayaran,
            'cicilan_bulanan' => $cicilanBulanan,
            'status_penjualan' => 'process',
            'created_by' => auth()->id()
        ]));
    }

    private function generateKodeBooking()
    {
        $prefix = 'BKG';
        $date = date('ym');
        $lastBooking = Booking::where('kode_booking', 'like', $prefix . $date . '%')
            ->orderBy('kode_booking', 'desc')
            ->first();

        if ($lastBooking) {
            $lastNumber = (int) substr($lastBooking->kode_booking, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    private function generateKodePenjualan()
    {
        $prefix = 'PJL';
        $date = date('ym');
        $lastPenjualan = Penjualan::where('kode_penjualan', 'like', $prefix . $date . '%')
            ->orderBy('kode_penjualan', 'desc')
            ->first();

        if ($lastPenjualan) {
            $lastNumber = (int) substr($lastPenjualan->kode_penjualan, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    public function getStatistics(Request $request)
    {
        try {
            // Query untuk menghitung statistics dengan filter
            $query = UnitDetail::query();
            
            // Filter berdasarkan project_id jika ada
            if ($request->has('project_id') && !empty($request->project_id)) {
                $query->whereHas('unit', function($q) use ($request) {
                    $q->where('idproject', $request->project_id);
                });
            }
            
            // Filter berdasarkan unit_id jika ada
            if ($request->has('unit_id') && !empty($request->unit_id)) {
                $query->where('idunit', $request->unit_id);
            }
            
            // Filter berdasarkan status jika ada
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            $totalUnits = $query->count();
            $tersedia = $query->clone()->where('status', 'tersedia')->count();
            $booking = $query->clone()->where('status', 'like', '%booking%')->count();
            $biCheck = $query->clone()->where('status', 'bi_check')->count();
            $pemberkasan = $query->clone()->where('status', 'pemberkasan_bank')->count();
            $acc = $query->clone()->where('status', 'acc')->count();
            $tidakAcc = $query->clone()->where('status', 'tidak_acc')->count();
            $akad = $query->clone()->where('status', 'akad')->count();
            $pencairan = $query->clone()->where('status', 'pencairan')->count();
            $bast = $query->clone()->where('status', 'bast')->count();
            $terjual = $query->clone()->where('status', 'terjual')->count();
            
            return response()->json([
                'total' => $totalUnits,
                'tersedia' => $tersedia,
                'booking' => $booking,
                'bi_check' => $biCheck,
                'pemberkasan_bank' => $pemberkasan,
                'acc' => $acc,
                'tidak_acc' => $tidakAcc,
                'akad' => $akad,
                'pencairan' => $pencairan,
                'bast' => $bast,
                'terjual' => $terjual,
                'tersedia_percent' => $totalUnits > 0 ? round(($tersedia / $totalUnits) * 100, 1) : 0,
                'booking_percent' => $totalUnits > 0 ? round(($booking / $totalUnits) * 100, 1) : 0,
                'terjual_percent' => $totalUnits > 0 ? round(($terjual / $totalUnits) * 100, 1) : 0,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getStatistics: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan'
            ], 500);
        }
    }
    
    // Method untuk mendapatkan detail unit termasuk data customer, booking, penjualan
    public function getDetail($id)
    {
        try {
            $unitDetail = UnitDetail::with([
                'unit', 
                'unit.project',
                'unit.jenisUnit',
                'customer',
                'booking',
                'penjualan'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $unitDetail
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }
}