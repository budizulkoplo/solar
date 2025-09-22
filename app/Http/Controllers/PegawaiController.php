<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Contracts\Encryption\DecryptException;

class PegawaiController extends Controller
{
    public function index(): View
    {
        return view('master.pegawai.list');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string',
            'email'         => 'required|email',
            'nik'           => 'required|string',
            'nip'           => 'nullable|string',
            'jabatan'       => 'nullable|string',
            'tanggal_masuk' => 'nullable|date',
            'alamat'        => 'nullable|string',
            'nohp'          => 'nullable|string',
        ]);

        // support fidusers baik yang plain id maupun yang terenkripsi
        $fid = $request->fidusers ?? null;
        if (!empty($fid)) {
            $id = $fid;
            if (!is_numeric($fid)) {
                try {
                    $decrypted = Crypt::decryptString($fid);
                    $id = is_numeric($decrypted) ? (int)$decrypted : $decrypted;
                } catch (DecryptException $e) {
                    // jika gagal dekripsi, gunakan apa adanya (fallback)
                    $id = $fid;
                } catch (\Exception $e) {
                    $id = $fid;
                }
            }
            $pegawai = User::find($id);
            if (!$pegawai) {
                return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
            }
        } else {
            $pegawai = new User();
            $pegawai->nip = $this->genCode();
            $pegawai->username = $request->username ?? strtolower(preg_replace('/\s+/', '', $request->name));
            $pegawai->password = Hash::make('12345678'); // default password
        }

        $pegawai->name          = $request->name;
        $pegawai->email         = $request->email;
        $pegawai->nik           = $request->nik;
        $pegawai->jabatan       = $request->jabatan;
        $pegawai->tanggal_masuk = $request->tanggal_masuk;
        $pegawai->alamat        = $request->alamat;
        $pegawai->nohp          = $request->nohp;
        $pegawai->status        = $request->has('status') && $request->status ? 'aktif' : 'nonaktif';
        $pegawai->save();

        return response()->json(['success' => true, 'message' => 'Data Pegawai berhasil disimpan', 'data' => $pegawai], 200);
    }

    public function getdata(Request $request)
    {
        $pegawai = User::select(['id','nip','nik','name','email','jabatan','tanggal_masuk','status','alamat','nohp']);

        return DataTables::of($pegawai)
            ->addIndexColumn()
            ->addColumn('aksi', function ($row) {
                return '
                    <span class="badge bg-info btn-edit" data-id="'.$row->id.'"><i class="bi bi-pencil-square"></i></span>
                    <span class="badge bg-danger btn-hapus" data-id="'.$row->id.'"><i class="fa-solid fa-trash-can"></i></span>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    // mengembalikan kode NIP baru
    public function getcode()
    {
        return response()->json($this->genCode(), 200);
    }

    // ambil 1 pegawai (dipakai untuk edit)
    public function show($id)
    {
        // support id plain atau terenkripsi
        if (!is_numeric($id)) {
            try {
                $decrypted = Crypt::decryptString($id);
                if (is_numeric($decrypted)) $id = (int)$decrypted;
            } catch (DecryptException $e) {
                // ignore, gunakan $id apa adanya
            } catch (\Exception $e) {
                // ignore
            }
        }

        $pegawai = User::find($id);
        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
        }

        return response()->json($pegawai, 200);
    }

    // hapus pegawai (soft delete jika model menggunakan SoftDeletes)
    public function destroy($id)
    {
        if (!is_numeric($id)) {
            try {
                $decrypted = Crypt::decryptString($id);
                if (is_numeric($decrypted)) $id = (int)$decrypted;
            } catch (DecryptException $e) {
                // ignore
            } catch (\Exception $e) {
                // ignore
            }
        }

        $pegawai = User::find($id);
        if (!$pegawai) {
            return response()->json(['success' => false, 'message' => 'Pegawai tidak ditemukan'], 404);
        }

        $pegawai->delete();

        return response()->json(['success' => true, 'message' => 'Pegawai dihapus'], 200);
    }

    private function genCode()
    {
        $total = User::withTrashed()->whereDate('created_at', date("Y-m-d"))->count();
        $nomorUrut = $total + 1;
        return 'PEG-' . date("ymd") . str_pad($nomorUrut, 3, '0', STR_PAD_LEFT);
    }
}
