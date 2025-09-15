<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Vendor::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    return '
                        <button class="btn btn-sm btn-info editVendor" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-danger deleteVendor" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('master.vendors.list');
    }

    public function store(Request $request)
    {
        Vendor::updateOrCreate(
            ['id' => $request->id],
            $request->only(['namavendor', 'jenis', 'npwp', 'rekening', 'telp', 'alamat'])
        );
        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        return Vendor::findOrFail($id);
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // khusus inline update jenis
    public function updateJenis(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->jenis = $request->jenis;
        $vendor->save();
        return response()->json(['success' => true]);
    }
}
