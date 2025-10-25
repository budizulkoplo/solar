<?php

namespace App\Http\Controllers;

use App\Models\KelompokJam;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class KelompokJamController extends Controller
{
    public function index()
    {
        return view('master.kelompokjam.index');
    }

    public function getdata()
    {
        $kelompok = KelompokJam::select(['id', 'shift', 'jammasuk', 'jampulang']);

        return DataTables::of($kelompok)
            ->addIndexColumn()
            ->addColumn('aksi', function($row){
                return '
                    <span class="badge bg-info btn-edit" data-id="'.$row->id.'"><i class="bi bi-pencil-square"></i></span>
                    <span class="badge bg-danger btn-hapus" data-id="'.$row->id.'"><i class="fa-solid fa-trash-can"></i></span>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shift' => 'required|string|max:100',
            'jammasuk' => 'required',
            'jampulang' => 'required',
        ]);

        $id = $request->fid ?? null;
        if($id){
            $kelompok = KelompokJam::find($id);
            if(!$kelompok){
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            }
        } else {
            $kelompok = new KelompokJam();
        }

        $kelompok->shift = $request->shift;
        $kelompok->jammasuk = $request->jammasuk;
        $kelompok->jampulang = $request->jampulang;
        $kelompok->save();

        return response()->json(['success' => true, 'message' => 'Data berhasil disimpan', 'data' => $kelompok]);
    }

    public function show($id)
    {
        $kelompok = KelompokJam::find($id);
        if(!$kelompok){
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json($kelompok);
    }

    public function destroy($id)
    {
        $kelompok = KelompokJam::find($id);
        if(!$kelompok){
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $kelompok->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }
}
