<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama mobile
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

        $hariIni = Carbon::today()->format('Y-m-d');
        $bulanIni = (int) Carbon::now()->format('m');
        $tahunIni = Carbon::now()->format('Y');
        $nik = $user->nik;

        // Presensi bulan ini: group by tanggal
        $presensiBulanIni = DB::table('presensi')
            ->where('nik', $nik)
            ->whereMonth('tgl_presensi', $bulanIni)
            ->whereYear('tgl_presensi', $tahunIni)
            ->orderBy('tgl_presensi')
            ->get()
            ->groupBy('tgl_presensi')
            ->map(function ($items) {
                return [
                    'masuk' => $items->where('inoutmode', 1)->first(),
                    'pulang' => $items->where('inoutmode', 2)->first(),
                ];
            });

        // Rekap presensi: jumlah hadir & terlambat (hanya absen masuk)
        $rekapPresensi = DB::table('presensi')
            ->selectRaw('COUNT(*) as jmlhadir, SUM(IF(jam_in > "08:00",1,0)) as jmlterlambat')
            ->where('nik', $nik)
            ->where('inoutmode', 1) // hanya absen masuk
            ->whereMonth('tgl_presensi', $bulanIni)
            ->whereYear('tgl_presensi', $tahunIni)
            ->first();

        // Rekap izin & sakit
        $rekapIzin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status="i",1,0)) as jmlizin, SUM(IF(status="s",1,0)) as jmlsakit')
            ->where('nik', $nik)
            ->whereMonth('tgl_izin', $bulanIni)
            ->whereYear('tgl_izin', $tahunIni)
            ->where('status_approved', 1)
            ->first();

        // Leaderboard hari ini: gabungkan jam masuk & pulang per user
        $leaderboard = DB::table('users')
            ->leftJoin('presensi as masuk', function($join) use ($hariIni) {
                $join->on('users.nik', '=', 'masuk.nik')
                     ->where('masuk.tgl_presensi', $hariIni)
                     ->where('masuk.inoutmode', 1);
            })
            ->leftJoin('presensi as pulang', function($join) use ($hariIni) {
                $join->on('users.nik', '=', 'pulang.nik')
                     ->where('pulang.tgl_presensi', $hariIni)
                     ->where('pulang.inoutmode', 2);
            })
            ->select(
                'users.nik',
                'users.name',
                'users.foto',
                'users.jabatan',
                'masuk.jam_in as jam_masuk',
                'pulang.jam_in as jam_pulang'
            )
            ->orderBy('masuk.jam_in')
            ->get();

        $namaBulan = [
            "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        return view('mobile.index', [
            'user' => $user,
            'drawerMenus' => $drawerMenus,
            'rekapPresensiBulanIni' => $presensiBulanIni,
            'rekappresensi' => $rekapPresensi,
            'rekapizin' => $rekapIzin,
            'leaderboard' => $leaderboard,
            'namabulan' => $namaBulan,
            'bulanini' => $bulanIni,
            'tahunini' => $tahunIni
        ]);
    }

    /**
     * Mendapatkan drawer menus untuk user tertentu
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    protected function getDrawerMenus($user)
    {
        return DB::table('mobilemenu')
            ->where('status', 'drawer')
            ->orderBy('idmenu')
            ->get();
    }
}
