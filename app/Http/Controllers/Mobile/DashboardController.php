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

        $hariIni = Carbon::today()->format('Y-m-d');
        $bulanIni = (int) Carbon::now()->format('m');
        $tahunIni = Carbon::now()->format('Y');
        $nik = $user->nik;

        // Ambil data jadwal untuk bulan ini sekaligus
        $startDate = Carbon::create($tahunIni, $bulanIni, 1)->startOfMonth();
        $endDate = Carbon::create($tahunIni, $bulanIni, 1)->endOfMonth();

        $jadwalBulanIni = DB::table('jadwal')
            ->where('pegawai_nik', $nik)
            ->whereBetween('tgl', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('tgl');

        // Ambil semua shift yang digunakan untuk mendapatkan jam shift sekaligus
        $shiftsUsed = $jadwalBulanIni->pluck('shift')->unique()->filter()->values();
        $jamShiftCollection = collect();

        if ($shiftsUsed->count() > 0) {
            $jamShiftCollection = DB::table('kelompokjam')
                ->whereIn('shift', $shiftsUsed)
                ->get()
                ->keyBy('shift');
        }

        // Presensi bulan ini: group by tanggal dengan informasi shift
        $presensiBulanIni = DB::table('presensi')
            ->where('nik', $nik)
            ->whereMonth('tgl_presensi', $bulanIni)
            ->whereYear('tgl_presensi', $tahunIni)
            ->orderBy('tgl_presensi')
            ->get()
            ->groupBy('tgl_presensi')
            ->map(function ($items, $tgl) use ($jadwalBulanIni, $jamShiftCollection) {
                $jadwalHariIni = $jadwalBulanIni->get($tgl);
                $shift = $jadwalHariIni->shift ?? '-';
                
                $jamShift = null;
                $jamPulangShift = null;
                
                if ($shift && $shift !== '-') {
                    $shiftData = $jamShiftCollection->get($shift);
                    $jamShift = $shiftData->jammasuk ?? null;
                    $jamPulangShift = $shiftData->jampulang ?? null;
                }

                return [
                    'masuk' => $items->where('inoutmode', 1)->first(),
                    'pulang' => $items->where('inoutmode', 2)->first(),
                    'shift' => $shift,
                    'jam_masuk_shift' => $jamShift,
                    'jam_pulang_shift' => $jamPulangShift,
                ];
            });

        // Hitung rekap presensi berdasarkan jadwal shift
        $rekapPresensi = $this->calculateRekapPresensi($nik, $bulanIni, $tahunIni);

        // Rekap izin & sakit
        $rekapIzin = DB::table('pengajuan_izin')
            ->selectRaw('SUM(IF(status="i",1,0)) as jmlizin, SUM(IF(status="s",1,0)) as jmlsakit')
            ->where('nik', $nik)
            ->whereMonth('tgl_izin', $bulanIni)
            ->whereYear('tgl_izin', $tahunIni)
            ->where('status_approved', 1)
            ->first();

        // Ambil jadwal untuk leaderboard hari ini
        $jadwalHariIni = DB::table('jadwal')
            ->where('tgl', $hariIni)
            ->get()
            ->keyBy('pegawai_nik');

        $shiftsLeaderboard = $jadwalHariIni->pluck('shift')->unique()->filter()->values();
        $jamShiftLeaderboard = collect();

        if ($shiftsLeaderboard->count() > 0) {
            $jamShiftLeaderboard = DB::table('kelompokjam')
                ->whereIn('shift', $shiftsLeaderboard)
                ->get()
                ->keyBy('shift');
        }

        // Leaderboard hari ini dengan informasi shift
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
                'masuk.foto_in as foto_in',
                'pulang.jam_in as jam_pulang',
                'pulang.foto_in as foto_out'
            )
            ->orderBy('masuk.jam_in')
            ->get()
            ->map(function ($item) use ($jadwalHariIni, $jamShiftLeaderboard) {
                $jadwalUser = $jadwalHariIni->get($item->nik);
                $shift = $jadwalUser->shift ?? '-';
                
                $jamShift = null;
                $jamPulangShift = null;
                
                if ($shift && $shift !== '-') {
                    $shiftData = $jamShiftLeaderboard->get($shift);
                    $jamShift = $shiftData->jammasuk ?? null;
                    $jamPulangShift = $shiftData->jampulang ?? null;
                }

                return (object) array_merge((array) $item, [
                    'shift' => $shift,
                    'jam_masuk_shift' => $jamShift,
                    'jam_pulang_shift' => $jamPulangShift,
                ]);
            });

        $namaBulan = [
            "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        return view('mobile.index', [
            'user' => $user,
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
     * Hitung rekap presensi berdasarkan jadwal shift
     */
    protected function calculateRekapPresensi(string $nik, int $bulan, int $tahun)
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        // Ambil data jadwal untuk bulan ini
        $jadwalCollection = DB::table('jadwal')
            ->where('pegawai_nik', $nik)
            ->whereBetween('tgl', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy('tgl');

        // Ambil data presensi untuk bulan ini
        $presensiCollection = DB::table('presensi')
            ->where('nik', $nik)
            ->whereBetween('tgl_presensi', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('tgl_presensi');

        $jmlHadir = 0;
        $jmlTerlambat = 0;

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $tgl = $currentDate->format('Y-m-d');
            
            // Cek apakah ada jadwal untuk tanggal ini
            $jadwal = $jadwalCollection->get($tgl);
            $shift = $jadwal->shift ?? '-';

            // Cek apakah ada presensi masuk untuk tanggal ini
            $presensiHariIni = $presensiCollection->get($tgl);
            $presensiMasuk = $presensiHariIni ? $presensiHariIni->firstWhere('inoutmode', 1) : null;

            if ($presensiMasuk) {
                $jmlHadir++;

                // Hitung keterlambatan berdasarkan shift
                if ($shift !== '-' && $shift !== 'libur' && strtolower($shift) !== 'libur') {
                    // Ambil jam masuk shift dari tabel kelompokjam
                    $jamShift = DB::table('kelompokjam')
                        ->where('shift', $shift)
                        ->first();

                    if ($jamShift && $jamShift->jammasuk) {
                        $jamMasukShift = $jamShift->jammasuk;
                        $jamMasukAktual = $presensiMasuk->jam_in;

                        // Hitung keterlambatan
                        if ($this->isTerlambat($tgl, $jamMasukAktual, $jamMasukShift, $shift)) {
                            $jmlTerlambat++;
                        }
                    }
                }
            }

            $currentDate->addDay();
        }

        return (object) [
            'jmlhadir' => $jmlHadir,
            'jmlterlambat' => $jmlTerlambat
        ];
    }

    /**
     * Cek apakah karyawan terlambat berdasarkan shift
     */
    protected function isTerlambat(string $tgl, string $jamAktual, string $jamShift, string $shift): bool
    {
        try {
            $shiftStart = Carbon::parse("$tgl $jamShift");
            $actualTime = Carbon::parse("$tgl $jamAktual");

            // Untuk shift malam (misal 22:00–06:00)
            $isNightShift = Carbon::parse($jamShift)->hour >= 18 && Carbon::parse($jamShift)->hour <= 23;

            if ($isNightShift) {
                // Untuk shift malam, tidak dihitung terlambat jika masuk sebelum jam shift
                // karena mungkin lembur dari hari sebelumnya
                if ($actualTime->lt($shiftStart) && $actualTime->hour >= 18) {
                    return false;
                }
                
                // Hitung terlambat hanya jika masuk setelah jam shift
                if ($actualTime->gt($shiftStart)) {
                    $diffSeconds = $shiftStart->diffInSeconds($actualTime);
                    return $diffSeconds > 60; // lebih dari 1 menit
                }
                
                return false;
            } else {
                // Untuk shift reguler
                if ($actualTime->gt($shiftStart)) {
                    $diffSeconds = $shiftStart->diffInSeconds($actualTime);
                    return $diffSeconds > 60; // lebih dari 1 menit
                }
                
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Konversi detik ke format jam:menit
     */
    protected function secondsToTime(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '';
        $seconds = abs($seconds);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }
}