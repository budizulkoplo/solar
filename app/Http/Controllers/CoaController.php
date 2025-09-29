<?php

namespace App\Http\Controllers;

use App\Models\Coa;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CoaController extends Controller
{
    public function index()
    {
        return view('master.coas.list');
    }

    public function getData(Request $request)
    {
        $data = Coa::with('parent')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('parent', function($row){
                return $row->parent ? $row->parent->name : '-';
            })
            ->addColumn('action', function($row){
                return '
                    <button class="btn btn-sm btn-warning editCoa" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteCoa" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coa,code',
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:coa,id',
            'level' => 'required|integer|min:1',
        ]);

        Coa::create($validated);

        return response()->json(['success' => true]);
    }

    public function show(Coa $coa)
    {
        return response()->json($coa);
    }

    public function update(Request $request, Coa $coa)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coa,code,'.$coa->id,
            'name' => 'required|string',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|exists:coa,id',
            'level' => 'required|integer|min:1',
        ]);

        $coa->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Coa $coa)
    {
        $coa->delete();
        return response()->json(['success' => true]);
    }
}
