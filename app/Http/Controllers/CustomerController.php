<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('customers.index');
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            $data = Customer::query()->orderBy('created_at', 'desc');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $actionBtn = '
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info viewCustomer" data-id="' . $row->id . '" title="Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-warning editCustomer" data-id="' . $row->id . '" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-danger deleteCustomer" data-id="' . $row->id . '" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    ';
                    return $actionBtn;
                })
                ->editColumn('penghasilan_bulanan', function($row) {
                    return $row->penghasilan_bulanan ? 'Rp ' . number_format($row->penghasilan_bulanan, 0, ',', '.') : '-';
                })
                ->editColumn('jenis_kelamin', function($row) {
                    return $row->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Tidak digunakan karena menggunakan modal
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nik' => 'required|digits:16|unique:customers',
            'alamat_ktp' => 'required|string',
            'rt_rw_ktp' => 'required|string|max:20',
            'kelurahan_ktp' => 'required|string|max:100',
            'kecamatan_ktp' => 'required|string|max:100',
            'kota_ktp' => 'required|string|max:100',
            'no_hp' => 'required|string|max:20',
            'pekerjaan' => 'required|string|max:100',
            'penghasilan_bulanan' => 'nullable|numeric|min:0',
            'email' => 'nullable|email|max:255',
            'provinsi_ktp' => 'nullable|string|max:100',
            'kode_pos_ktp' => 'nullable|string|max:10',
            'nama_ibu_kandung' => 'nullable|string|max:255',
            'status_pernikahan' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate kode customer
            $latestCustomer = Customer::withTrashed()->orderBy('id', 'desc')->first();
            $nextNumber = $latestCustomer ? intval(substr($latestCustomer->kode_customer, 3)) + 1 : 1;
            $kodeCustomer = 'CUS' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $customer = Customer::create(array_merge($request->all(), [
                'kode_customer' => $kodeCustomer
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil ditambahkan',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        $validator = validator($request->all(), [
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nik' => 'required|digits:16|unique:customers,nik,' . $id,
            'alamat_ktp' => 'required|string',
            'rt_rw_ktp' => 'required|string|max:20',
            'kelurahan_ktp' => 'required|string|max:100',
            'kecamatan_ktp' => 'required|string|max:100',
            'kota_ktp' => 'required|string|max:100',
            'no_hp' => 'required|string|max:20',
            'pekerjaan' => 'required|string|max:100',
            'penghasilan_bulanan' => 'nullable|numeric|min:0',
            'email' => 'nullable|email|max:255',
            'provinsi_ktp' => 'nullable|string|max:100',
            'kode_pos_ktp' => 'nullable|string|max:10',
            'nama_ibu_kandung' => 'nullable|string|max:255',
            'status_pernikahan' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil diperbarui',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        // Cek apakah customer sudah memiliki relasi
        if ($customer->unitDetails()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus customer yang sudah memiliki unit'
            ], 400);
        }

        if ($customer->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus customer yang sudah memiliki booking'
            ], 400);
        }

        if ($customer->penjualans()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus customer yang sudah memiliki penjualan'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $customer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer details for view in modal
     */
    public function getDetail($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }
}