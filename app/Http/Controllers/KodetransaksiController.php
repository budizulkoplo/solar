<?php

namespace App\Http\Controllers;

use App\Models\Kodetransaksi;
use App\Models\Coa;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KodetransaksiController extends Controller
{
    public function index()
    {
        return view('master.kodetransaksi.list', [
            'coa' => Coa::all()
        ]);
    }

    public function getData(Request $request)
    {
        $data = Kodetransaksi::with('coa')->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_coa', fn($row) => $row->coa ? $row->coa->name : '-')
            ->addColumn('action', function($row){
                return '
                    <button class="btn btn-sm btn-warning editData" data-id="'.$row->id.'">Edit</button>
                    <button class="btn btn-sm btn-danger deleteData" data-id="'.$row->id.'">Hapus</button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function edit($id)
    {
        $kt = Kodetransaksi::findOrFail($id);
        return response()->json($kt);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kodetransaksi' => 'required|string|unique:kodetransaksi,kodetransaksi',
            'transaksi'     => 'required|string',
            'idcoa'         => 'required|exists:coa,id'
        ]);

        Kodetransaksi::create($validated);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, Kodetransaksi $kodetransaksi)
    {
        $validated = $request->validate([
            'kodetransaksi' => 'required|string|unique:kodetransaksi,kodetransaksi,'.$kodetransaksi->id,
            'transaksi'     => 'required|string',
            'idcoa'         => 'required|exists:coa,id'
        ]);

        $kodetransaksi->update($validated);
        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $kt = Kodetransaksi::findOrFail($id);
        $kt->delete();
        return response()->json(['success' => true]);
    }

    public function updateCoa(Request $request, $id)
    {
        $request->validate([
            'idcoa' => 'required|exists:coa,id'
        ]);

        $kt = Kodetransaksi::findOrFail($id);
        $kt->idcoa = $request->idcoa;
        $kt->save();

        return response()->json(['success' => true]);
    }
}
