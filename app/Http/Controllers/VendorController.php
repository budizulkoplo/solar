<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    public function index()
    {
        return view('master.vendors.list'); // Blade file
    }

    public function getData(Request $request)
    {
        $data = Vendor::query();

        return DataTables::of($data)
            ->addIndexColumn() // buat DT_RowIndex
            ->addColumn('aksi', function ($row) {
                return '
                    <button class="btn btn-sm btn-info editVendor" data-id="'.$row->id.'"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger deleteVendor" data-id="'.$row->id.'"><i class="bi bi-trash"></i></button>
                ';
            })
            ->rawColumns(['aksi'])
            // Nonaktifkan search/filter untuk kolom DT_RowIndex
            ->filterColumn('DT_RowIndex', function($query, $keyword) {
                // kosongkan, jangan filter pakai DT_RowIndex
            })
            ->order(function($query) use($request) {
                if($request->order) {
                    $columns = $request->columns;
                    $order = $request->order[0];
                    $colIndex = $order['column'];
                    $dir = $order['dir'];
                    $colName = $columns[$colIndex]['data'];

                    // jika kolom index, urutkan pakai 'id'
                    if($colName === 'DT_RowIndex'){
                        $query->orderBy('id', $dir);
                    } else {
                        $query->orderBy($colName, $dir);
                    }
                }
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'namavendor' => 'required|string|max:255',
            'jenis' => 'required|in:pekerjaan,material',
        ]);

        Vendor::updateOrCreate(
            ['id' => $request->id],
            $request->only(['namavendor', 'jenis', 'npwp', 'rekening', 'telp', 'alamat'])
        );

        return response()->json(['success' => true]);
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        // jika null, set default 'pekerjaan'
        if(is_null($vendor->jenis)){
            $vendor->jenis = 'pekerjaan';
        }
        return $vendor;
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function updateJenis(Request $request, $id)
    {
        $request->validate([
            'jenis' => 'required|in:pekerjaan,material'
        ]);

        $vendor = Vendor::findOrFail($id);
        $vendor->jenis = $request->jenis;
        $vendor->save();

        return response()->json(['success' => true]);
    }
}
