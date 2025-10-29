<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PlottingUnitKerjaController extends Controller
{
    public function index()
    {
        $unitkerja = UnitKerja::orderBy('company_name')->get();
        return view('master.plotting_unitkerja.index', compact('unitkerja'));
    }

    public function getdata(Request $request)
    {
        $users = User::with('unitkerja')->select(['id', 'name', 'jabatan', 'id_unitkerja']);

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('unitkerja', function ($row) {
                return $row->unitkerja->company_name ?? '-';
            })
            ->addColumn('aksi', function ($row) {
                return '
                    <span class="badge bg-primary btn-plot" data-id="'.$row->id.'" data-unit="'.$row->id_unitkerja.'">
                        <i class="bi bi-diagram-3"></i> Atur Unit
                    </span>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function updateUnit(Request $request)
    {
        $request->validate([
            'id_user' => 'required|integer|exists:users,id',
            'id_unitkerja' => 'nullable|integer|exists:company_units,id',
        ]);

        $user = User::find($request->id_user);
        $user->id_unitkerja = $request->id_unitkerja;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Unit kerja pegawai berhasil diperbarui.'
        ]);
    }
}
