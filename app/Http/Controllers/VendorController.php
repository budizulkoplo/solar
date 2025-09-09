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
            $vendors = Vendor::query();
            return DataTables::of($vendors)
                ->addIndexColumn()
                ->addColumn('aksi', function ($row) {
                    return '
                        <span class="badge rounded-pill bg-info formcell" data-id="'.$row->id.'">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <span class="badge rounded-pill bg-danger deleteVendor" data-id="'.$row->id.'">
                            <i class="fa-solid fa-trash-can"></i>
                        </span>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        return view('master.vendor.list');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_vendor'   => 'required|string|max:255',
            'alamat'        => 'nullable|string',
            'telepon'       => 'nullable|string|max:50',
            'email'         => 'nullable|email',
            'kontak_person' => 'nullable|string|max:255',
            'status'        => 'required|in:aktif,nonaktif',
        ]);

        $vendor = Vendor::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['success' => true, 'vendor' => $vendor]);
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        return Vendor::findOrFail($id);
    }

}
