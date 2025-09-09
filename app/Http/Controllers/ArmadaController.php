<?php
namespace App\Http\Controllers;

use App\Models\Armada;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ArmadaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $armadas = Armada::with('vendor')->select('armadas.*');
            return DataTables::of($armadas)
                ->addIndexColumn()
                ->addColumn('vendor', function ($row) {
                    return $row->vendor->nama_vendor ?? '-';
                })
                ->addColumn('aksi', function ($row) {
                    return '
                        <span class="badge rounded-pill bg-info formcell" data-id="'.$row->id.'">
                            <i class="bi bi-pencil-square"></i>
                        </span>
                        <span class="badge rounded-pill bg-danger deleteArmada" data-id="'.$row->id.'">
                            <i class="fa-solid fa-trash-can"></i>
                        </span>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

        $vendors = Vendor::all();
        return view('master.armada.list', compact('vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'nopol'     => 'required|string|max:50',
            'panjang'   => 'required|integer',
            'lebar'     => 'required|integer',
            'tinggi'    => 'required|integer',
        ]);

        $armada = Armada::updateOrCreate(
            ['id' => $request->id],
            $validated
        );

        return response()->json(['success' => true, 'armada' => $armada]);
    }

    public function show($id)
    {
        return Armada::findOrFail($id);
    }

    public function destroy($id)
    {
        Armada::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
