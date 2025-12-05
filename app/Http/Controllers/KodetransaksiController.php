<?php

namespace App\Http\Controllers;

use App\Models\Kodetransaksi;
use App\Models\Coa;
use App\Models\TransaksiHdr;
use App\Models\NeracaHdr;
use App\Models\LabaRugiHdr;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KodetransaksiController extends Controller
{
    public function index()
    {
        return view('master.kodetransaksi.list', [
            'coa' => Coa::all(),
            'transaksiHeaders' => TransaksiHdr::all(),
            'neracaHeaders' => NeracaHdr::all(),
            'labaRugiHeaders' => LabaRugiHdr::all()
        ]);
    }

    public function getData(Request $request)
    {
        $data = Kodetransaksi::with(['coa', 'header', 'neraca', 'labarugi'])->get();

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('nama_coa', fn($row) => $row->coa ? $row->coa->name : '-')
            ->addColumn('nama_header', fn($row) => $row->header ? $row->header->keterangan : '-')
            ->addColumn('nama_neraca', fn($row) => $row->neraca ? $row->neraca->rincian : '-')
            ->addColumn('nama_labarugi', fn($row) => $row->labarugi ? $row->labarugi->rincian : '-')
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
            'idheader'      => 'nullable|exists:transaksi_hdr,id',
            'idcoa'         => 'nullable|exists:coa,id',
            'idneraca'      => 'nullable|exists:neraca_hdr,id',
            'idlabarugi'    => 'nullable|exists:labarugi_hdr,id'
        ]);

        Kodetransaksi::create($validated);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, Kodetransaksi $kodetransaksi)
    {
        $validated = $request->validate([
            'kodetransaksi' => 'required|string|unique:kodetransaksi,kodetransaksi,'.$kodetransaksi->id,
            'transaksi'     => 'required|string',
            'idheader'      => 'nullable|exists:transaksi_hdr,id',
            'idcoa'         => 'nullable|exists:coa,id',
            'idneraca'      => 'nullable|exists:neraca_hdr,id',
            'idlabarugi'    => 'nullable|exists:labarugi_hdr,id'
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

    public function updateField(Request $request, $id)
    {
        $request->validate([
            'field' => 'required|in:idheader,idcoa,idneraca,idlabarugi',
            'value' => 'nullable|integer'
        ]);

        $kt = Kodetransaksi::findOrFail($id);
        $kt->{$request->field} = $request->value;
        $kt->save();

        return response()->json(['success' => true]);
    }
}