<?php

namespace App\Http\Controllers;

use App\Models\PengajuanIzin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Yajra\DataTables\Facades\DataTables;

class PengajuanIzinController extends Controller
{
    public function index()
    {
        return view('hris.pengajuan_izin.list');
    }

    public function getdata(Request $request)
    {
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));

        $izin = PengajuanIzin::with('user')
            ->select(['id', 'nik', 'tgl_izin', 'status', 'izin_mulai', 'izin_selesai', 'keterangan', 'status_approved', 'lampiran'])
            ->when($bulan, fn($q) => $q->whereMonth('tgl_izin', $bulan))
            ->when($tahun, fn($q) => $q->whereYear('tgl_izin', $tahun))
            ->orderByDesc('tgl_izin');

        return DataTables::of($izin)
            ->addIndexColumn()
            ->addColumn('pegawai', fn($row) => $row->user->name ?? '-')
            ->addColumn('status_text', function ($row) {
                return match ($row->status) {
                    'i' => 'Izin',
                    's' => 'Sakit',
                    'c' => 'Cuti',
                    default => '-',
                };
            })
            ->addColumn('approved_text', function ($row) {
                return match ($row->status_approved) {
                    '0' => '<span class="badge bg-secondary">Pending</span>',
                    '1' => '<span class="badge bg-success">Disetujui</span>',
                    '2' => '<span class="badge bg-danger">Ditolak</span>',
                    default => '-',
                };
            })
            ->addColumn('aksi', function ($row) {
                $btnEdit = '<span class="badge bg-info btn-edit" data-id="' . $row->id . '" style="cursor:pointer;">
                                <i class="bi bi-pencil-square"></i>
                            </span>';
                $btnHapus = '<span class="badge bg-danger btn-hapus" data-id="' . $row->id . '" style="cursor:pointer;">
                                <i class="fa-solid fa-trash-can"></i>
                            </span>';

                // tombol surat sakit
                $btnSurat = '';
                if ($row->status === 's' && !empty($row->lampiran)) {
                    $url = asset('storage/surat_sakit/' . $row->lampiran);
                    $btnSurat = '<a href="' . $url . '" target="_blank" class="badge bg-primary" title="Lihat Surat Sakit">
                                    <i class="bi bi-file-earmark-medical"></i>
                                </a>';
                }

                return $btnSurat . ' ' . $btnEdit . ' ' . $btnHapus;
            })
            ->rawColumns(['aksi', 'approved_text'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik'          => 'required|string|max:20',
            'tgl_izin'     => 'required|date',
            'status'       => 'required|in:i,s,c',
            'keterangan'   => 'required|string|max:255',
        ]);

        $fid = $request->fidizin ?? null;

        // cek apakah edit atau create baru
        if (!empty($fid)) {
            $id = $fid;
            if (!is_numeric($fid)) {
                try {
                    $decrypted = Crypt::decryptString($fid);
                    $id = is_numeric($decrypted) ? (int)$decrypted : $decrypted;
                } catch (DecryptException $e) {}
            }

            $izin = PengajuanIzin::find($id);
            if (!$izin) {
                return response()->json(['success' => false, 'message' => 'Data izin tidak ditemukan'], 404);
            }
        } else {
            $izin = new PengajuanIzin();
        }

        $izin->nik             = $request->nik;
        $izin->tgl_izin        = $request->tgl_izin;
        $izin->status          = $request->status;
        $izin->keterangan      = $request->keterangan;

        // kalau update, boleh ubah status_approved
        if ($request->has('approved')) {
            $izin->status_approved = $request->approved;
        } else {
            // jika baru buat, default pending (belum disetujui)
            $izin->status_approved = '0';
        }

        $izin->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan izin berhasil disimpan'
        ], 200);
    }

    public function show($id)
    {
        if (!is_numeric($id)) {
            try {
                $decrypted = Crypt::decryptString($id);
                if (is_numeric($decrypted)) $id = (int)$decrypted;
            } catch (DecryptException $e) {}
        }

        $izin = PengajuanIzin::with('user')->find($id);
        if (!$izin) {
            return response()->json(['success' => false, 'message' => 'Data izin tidak ditemukan'], 404);
        }

        return response()->json($izin, 200);
    }

    public function destroy($id)
    {
        if (!is_numeric($id)) {
            try {
                $decrypted = Crypt::decryptString($id);
                if (is_numeric($decrypted)) $id = (int)$decrypted;
            } catch (DecryptException $e) {}
        }

        $izin = PengajuanIzin::find($id);
        if (!$izin) {
            return response()->json(['success' => false, 'message' => 'Data izin tidak ditemukan'], 404);
        }

        $izin->delete();
        return response()->json(['success' => true, 'message' => 'Data izin berhasil dihapus'], 200);
    }

    public function getPegawaiSelect2(Request $request)
    {
        $search = $request->input('q', '');
        $users = User::select('nik', 'name')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        $results = $users->map(fn($u) => [
            'id' => $u->nik,
            'text' => "{$u->nik} - {$u->name}",
        ]);

        return response()->json(['results' => $results]);
    }

    
}
