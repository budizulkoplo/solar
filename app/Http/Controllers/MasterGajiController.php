<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterGaji;
use App\Models\User;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterGajiController extends Controller
{
    public function index()
    {
        return view('master.gaji.index');
    }

    // 🔹 DataTables daftar pegawai dengan status verifikasi gaji
    public function getPegawai(Request $request)
    {
        $data = User::select('id','nip','nik','name','status')
            ->with(['mastergaji' => function($q){ $q->latest('tgl_aktif'); }]);

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('verif_status', function($row){
                $latest = $row->mastergaji->first();
                if(!$latest) return '<span class="badge bg-secondary">Belum Ada</span>';
                return $latest->verifikasi == 1
                    ? '<span class="badge bg-success">Terverifikasi</span>'
                    : '<span class="badge bg-warning text-dark">Belum Verifikasi</span>';
            })
            ->addColumn('aksi', function($row){
                return '<button class="btn btn-sm btn-info btn-gaji" data-nik="'.$row->nik.'" data-name="'.$row->name.'">
                            <i class="bi bi-cash-stack"></i> Master Gaji
                        </button>';
            })
            ->rawColumns(['verif_status','aksi'])
            ->make(true);
    }

    // 🔹 Ambil riwayat gaji per pegawai
    public function riwayat($nik)
    {
        $data = MasterGaji::where('nik',$nik)
            ->orderBy('tgl_aktif','desc')->get();

        return response()->json($data);
    }

    // 🔹 Simpan / update data gaji
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik' => 'required|exists:users,nik',
            'tgl_aktif' => 'required|date',
            'gajipokok' => 'nullable|numeric',
            'masakerja' => 'nullable|numeric',
            'komunikasi' => 'nullable|numeric',
            'transportasi' => 'nullable|numeric',
            'konsumsi' => 'nullable|numeric',
            'tunj_asuransi' => 'nullable|numeric',
            'jabatan' => 'nullable|numeric',
            'asuransi' => 'nullable|numeric',
            'verifikasi' => 'in:0,1'
        ]);

        MasterGaji::create($validated);

        return response()->json(['success'=>true,'message'=>'Data gaji berhasil disimpan']);
    }

    public function destroy($id)
    {
        $gaji = MasterGaji::find($id);
        if(!$gaji) return response()->json(['success'=>false,'message'=>'Data tidak ditemukan'],404);
        $gaji->delete();
        return response()->json(['success'=>true,'message'=>'Data berhasil dihapus']);
    }
    
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tgl_aktif'     => 'required|date',
            'gajipokok'     => 'nullable|numeric',
            'masakerja'     => 'nullable|numeric',
            'komunikasi'    => 'nullable|numeric',
            'transportasi'  => 'nullable|numeric',
            'konsumsi'      => 'nullable|numeric',
            'tunj_asuransi' => 'nullable|numeric',
            'jabatan'       => 'nullable|numeric',
            'asuransi'      => 'nullable|numeric',
            'verifikasi'    => 'required|in:0,1',
        ]);

        $gaji = MasterGaji::find($id);
        if(!$gaji){
            return response()->json(['success'=>false,'message'=>'Data gaji tidak ditemukan'],404);
        }

        $gaji->update($validated);

        return response()->json(['success'=>true,'message'=>'Data gaji berhasil diperbarui']);
    }

}
