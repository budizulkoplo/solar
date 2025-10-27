<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PegawaiDtl;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Contracts\Encryption\DecryptException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PegawaiController extends Controller
{
    public function index(): View
    {
        $unitkerja = UnitKerja::select('id', 'company_name', 'lokasi')->get();
        return view('master.pegawai.list', compact('unitkerja'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip'                => 'required|string|max:30',
            'nik'                => 'required|string|max:20',
            'name'               => 'required|string|max:150',
            'jabatan'            => 'required|in:Danru,Anggota',
            'id_unitkerja'       => 'required|integer|exists:company_units,id',
            'awal_kontrak'       => 'required|date',
            'akhir_kontrak'      => 'nullable|date',
            'email'              => 'nullable|email',
            'nohp'               => 'nullable|string|max:50',
            'alamat_ktp'         => 'nullable|string',
            'tempat_lahir'       => 'nullable|string|max:100',
            'tanggal_lahir'      => 'nullable|date',
            'kode_pos'           => 'nullable|string|max:10',
            'jenis_kelamin'      => 'nullable|in:L,P',
            'gol_darah'          => 'nullable|in:A,B,AB,O',
            'status_perkawinan'  => 'nullable|in:LAJANG,KAWIN,CERAI',
            'jumlah_anak'        => 'nullable|integer',
            'nama_ibu_kandung'   => 'nullable|string|max:150',
            'no_jkn_kis'         => 'nullable|string|max:30',
            'no_kpj'             => 'nullable|string|max:30',
            'pendidikan_terakhir'=> 'nullable|string|max:100',
            'status'             => 'required|in:aktif,nonaktif',
        ]);

        DB::beginTransaction();
        try {
            // Jika fidusers ada => update, kalau tidak => create
            if ($request->fidusers) {
                $user = User::find($request->fidusers);
                if (!$user) {
                    return response()->json(['success'=>false,'message'=>'Pegawai tidak ditemukan'],404);
                }

                // Update user
                $user->update([
                    'nip'           => $request->nip,
                    'nik'           => $request->nik,
                    'name'          => $request->name,
                    'email'         => $request->email,
                    'jabatan'       => $request->jabatan,
                    'id_unitkerja'  => $request->id_unitkerja,
                    'tanggal_masuk' => $request->awal_kontrak,
                    'status'        => $request->status,
                    'nohp'          => $request->nohp,
                    'alamat'        => $request->alamat_ktp,
                    'email_verified_at'=> now(),
                ]);
            } else {
                // Create baru
                $user = User::create([
                    'nik'           => $request->nik,
                    'nip'           => $request->nip,
                    'username'      => $request->nik,
                    'name'          => $request->name,
                    'email'         => $request->email,
                    'jabatan'       => $request->jabatan,
                    'id_unitkerja'  => $request->id_unitkerja,
                    'tanggal_masuk' => $request->awal_kontrak,
                    'status'        => $request->status,
                    'nohp'          => $request->nohp,
                    'alamat'        => $request->alamat_ktp,
                    'password'      => Hash::make('12345678'),
                    'email_verified_at'=> now(),
                ]);
            }

            // Sinkron ke pegawai_dtl
            PegawaiDtl::updateOrCreate(
                ['nik' => $request->nik],
                [
                    'awal_kontrak'       => $request->awal_kontrak,
                    'akhir_kontrak'      => $request->akhir_kontrak,
                    'nama'               => $request->name,
                    'no_jkn_kis'         => $request->no_jkn_kis,
                    'no_kpj'             => $request->no_kpj,
                    'tempat_lahir'       => $request->tempat_lahir,
                    'tanggal_lahir'      => $request->tanggal_lahir,
                    'alamat_ktp'         => $request->alamat_ktp,
                    'kode_pos'           => $request->kode_pos,
                    'jenis_kelamin'      => $request->jenis_kelamin,
                    'gol_darah'          => $request->gol_darah,
                    'status_perkawinan'  => $request->status_perkawinan,
                    'jumlah_anak'        => $request->jumlah_anak,
                    'nama_ibu_kandung'   => $request->nama_ibu_kandung,
                    'no_hp'              => $request->nohp,
                    'email_aktif'        => $request->email,
                    'pendidikan_terakhir'=> $request->pendidikan_terakhir,
                ]
            );

            DB::commit();
            return response()->json(['success'=>true,'message'=>'Data Pegawai berhasil disimpan','data'=>$user]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Gagal menyimpan data: '.$e->getMessage()],500);
        }
    }


        public function getdata(Request $request)
        {
            $pegawai = User::with('unitkerja:id,company_name')
            ->whereHas('pegawaiDtl')
                ->select(['id', 'nip', 'nik', 'name', 'email', 'jabatan', 'tanggal_masuk', 'status', 'alamat', 'nohp', 'id_unitkerja']);

            return DataTables::of($pegawai)
                ->addIndexColumn()
                ->addColumn('unitkerja', function ($row) {
                    return $row->unitkerja->company_name ?? '-';
                })
                ->addColumn('aksi', function ($row) {
                    return '
                        <span class="badge bg-info btn-edit" data-id="'.$row->id.'"><i class="bi bi-pencil-square"></i></span>
                        <span class="badge bg-danger btn-hapus" data-id="'.$row->id.'"><i class="fa-solid fa-trash-can"></i></span>
                    ';
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }

    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                $id = Crypt::decryptString($id);
            }
        } catch (\Throwable $e) {}

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
        }

        // Ambil detail berdasarkan nik
        $detail = PegawaiDtl::where('nik', $user->nik)->first();

        return response()->json([
            'success' => true,
            'user'    => $user,
            'detail'  => $detail ?? new \stdClass(), // jika null, kirim object kosong agar JS tidak error
        ]);
    }


    public function destroy($id)
    {
        try {
            if (!is_numeric($id)) {
                $id = Crypt::decryptString($id);
            }
        } catch (\Throwable $e) {}

        $user = User::find($id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
        }

        DB::transaction(function () use ($user) {
            PegawaiDtl::where('nik', $user->nik)->delete();
            $user->delete();
        });

        return response()->json(['success' => true, 'message' => 'Pegawai berhasil dihapus'], 200);
    }

    private function genCode()
    {
        $total = User::withTrashed()->whereDate('created_at', date("Y-m-d"))->count();
        $nomorUrut = $total + 1;
        return 'PEG-' . date("ymd") . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT);
    }
}
