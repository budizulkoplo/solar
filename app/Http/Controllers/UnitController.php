<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\UnitDetail;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        return view('master.units.list');
    }


    public function getData(Request $request)
{
    $data = Unit::with(['project','jenisUnit'])->get();

    return DataTables::of($data)
        ->addIndexColumn() // <--- penting biar ada DT_RowIndex
        ->addColumn('project', function($row){
            return $row->project ? $row->project->namaproject : '-';
        })
        ->addColumn('jenisunit', function($row){
            return $row->jenisUnit ? $row->jenisUnit->jenisunit : '-';
        })
        ->addColumn('action', function($row){
            return '
                <button class="btn btn-sm btn-warning editUnit" data-id="'.$row->id.'">Edit</button>
                <button class="btn btn-sm btn-danger deleteUnit" data-id="'.$row->id.'">Delete</button>
            ';
        })
        ->rawColumns(['action'])
        ->make(true);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'idproject' => 'required|integer',
            'namaunit' => 'required|string',
            'idjenis' => 'required|integer',
            'blok' => 'nullable|string',
            'luastanah' => 'nullable|string',
            'luasbangunan' => 'nullable|string',
            'hargadasar' => 'required|numeric',
            'jumlah' => 'required|integer|min:1',
        ]);

        $unit = Unit::create($validated);

        // Generate unit_details otomatis
        for ($i = 0; $i < $unit->jumlah; $i++) {
            UnitDetail::create([
                'idunit' => $unit->id,
                'status' => 'tersedia',
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function show(Unit $unit)
    {
        return response()->json($unit->load(['project','jenis','details']));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'idproject' => 'required|integer',
            'namaunit' => 'required|string',
            'idjenis' => 'required|integer',
            'blok' => 'nullable|string',
            'luastanah' => 'nullable|string',
            'luasbangunan' => 'nullable|string',
            'hargadasar' => 'required|numeric',
            'jumlah' => 'required|integer|min:1',
        ]);

        $unit->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();
        return response()->json(['success' => true]);
    }
}
