<?php
namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class KalenderController extends BaseMobileController
{
    protected $employees;
    protected $pegawaiNik;
    protected $selectedEmployee;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = auth()->user();
            $this->pegawaiNik = $this->user ? $this->user->nik : null;

            $this->employees = DB::table('users')
                ->select('nik', 'name')
                ->where('status', '<>', 'nonaktif')
                ->orderBy('name')
                ->get();

            $this->selectedEmployee = $this->employees->firstWhere('nik', $this->pegawaiNik);

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        return $this->renderKalenderView($request, 'mobile.kalender.index');
    }

    public function lembur(Request $request)
    {
        return $this->renderKalenderView($request, 'mobile.kalender.lembur');
    }

    public function statistik(Request $request)
    {
        $bulan = $this->validateMonth($request->bulan ?? date('Y-m'));

        $viewData = [
            'employees'  => $this->employees,
            'pegawaiNik' => $this->pegawaiNik,
            'bulan'      => $bulan,
        ];

        if ($this->selectedEmployee) {
            $data = $this->prepareKalenderData($bulan);

            // Hitung statistik
            $stats = $this->calculateStatistics($data['dataKalender']);

            // Hitung jumlah perizinan
            $jumlahIzin = collect($data['dataKalender'])
                ->filter(fn($item) => isset($item['status_khusus']) && strtolower(trim($item['status_khusus'])) === 'izin')
                ->count();

            $jumlahSakit = collect($data['dataKalender'])
                ->filter(fn($item) => isset($item['status_khusus']) && strtolower(trim($item['status_khusus'])) === 'sakit')
                ->count();

            $jumlahCuti = collect($data['dataKalender'])
                ->filter(fn($item) => isset($item['status_khusus']) && strtolower(trim($item['status_khusus'])) === 'cuti')
                ->count();

            // Hitung total jam lembur dari inoutmode 3 & 4
            $totalLemburFormatted = $this->calculateTotalLembur($data['dataKalender']);

            // Hitung total hari lembur
            $totalHariLembur = collect($data['dataKalender'])
                ->filter(fn($item) => !empty($item['lembur_in']) && !empty($item['lembur_out']))
                ->count();

            // Grafik keterlambatan
            $chartLabels = [];
            $chartValues = [];
            foreach ($data['dataKalender'] as $tgl => $row) {
                $chartLabels[] = Carbon::parse($tgl)->translatedFormat('d M');
                if (!empty($row['jam_masuk']) && !empty($row['jam_masuk_shift']) && empty($row['status_khusus'])) {
                    $masuk = strtotime(strip_tags($row['jam_masuk']));
                    $shift = strtotime($row['jam_masuk_shift']);
                    $chartValues[] = max(0, round(($masuk - $shift) / 60, 1)); // menit keterlambatan
                } else {
                    $chartValues[] = 0;
                }
            }

            // Pass semua ke view
            $viewData = array_merge($viewData, [
                'selectedEmployee' => $this->selectedEmployee,
                'totalWorkDays'    => $stats['workDays'],
                'jumlahIzin'       => $jumlahIzin,
                'jumlahSakit'      => $jumlahSakit,
                'jumlahCuti'       => $jumlahCuti,
                'terlambatFormatted'=> $this->secondsToTime($stats['terlambat']),
                'totalLemburFormatted' => $totalLemburFormatted,
                'totalHariLembur'  => $totalHariLembur,
                'avgSelisihMasuk'  => $stats['avgSelisihMasuk'],
                'avgSelisihPulang' => $stats['avgSelisihPulang'],
                'countShiftDays'   => $stats['countShiftDays'],
                'dataKalender'     => $data['dataKalender'],
                'chartLabels'      => $chartLabels,
                'chartValues'      => $chartValues,
                'totalTugasLuar'   => $stats['tugasLuar'],
                'totalHariPeriode' => count($data['dataKalender']),
                'jumlahTepatWaktu' => $stats['jumlahTepatWaktu'],
                'jumlahTerlambat'  => $stats['jumlahTerlambat'],
                'jumlahPulangAwal' => $stats['jumlahPulangAwal'],
                'jumlahPulangLambat'=> $stats['jumlahPulangLambat'],
                'totalPerizinan'   => $jumlahIzin + $jumlahSakit + $jumlahCuti,
            ]);
        }

        return view('mobile.kalender.statistik', $viewData);
    }

    // Method baru untuk menghitung total lembur dari inoutmode 3 & 4
    protected function calculateTotalLembur(array $dataKalender): string
    {
        $totalLemburSeconds = 0;

        foreach ($dataKalender as $data) {
            // Hitung lembur hanya jika ada kedua waktu (lembur_in dan lembur_out)
            if (!empty($data['lembur_in']) && !empty($data['lembur_out'])) {
                try {
                    $lemburIn = Carbon::parse($data['lembur_in']);
                    $lemburOut = Carbon::parse($data['lembur_out']);
                    
                    // Jika waktu out lebih kecil dari in, tambahkan 1 hari (untuk lembur malam)
                    if ($lemburOut->lt($lemburIn)) {
                        $lemburOut->addDay();
                    }
                    
                    $totalLemburSeconds += $lemburIn->diffInSeconds($lemburOut);
                } catch (\Exception $e) {
                    // Skip jika parsing error
                    continue;
                }
            }
        }

        return $this->secondsToTime($totalLemburSeconds);
    }

    protected function renderKalenderView(Request $request, string $view)
    {
        $bulan = $this->validateMonth($request->bulan ?? date('Y-m'));

        $viewData = [
            'employees'  => $this->employees,
            'bulan'      => $bulan,
            'pegawaiNik' => $this->pegawaiNik,
        ];

        if ($this->selectedEmployee) {
            $data  = $this->prepareKalenderData($bulan);
            $stats = $this->calculateStatistics($data['dataKalender']);

            $viewData = array_merge($viewData, [
                'selectedEmployee'     => $this->selectedEmployee,
                'dataKalender'         => $data['dataKalender'],
                'weeks'                => $data['weeks'],
                'liburNasional'        => $data['liburNasional'],
                'liburBulanIni'        => $data['liburBulanIni'],
                'stats'                => $stats,
            ]);
        }

        return view($view, $viewData);
    }

    protected function validateMonth(string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    // === Prepare Kalender Data ===
    protected function prepareKalenderData(string $bulan): array
    {
        if (!$this->pegawaiNik) {
            return [
                'dataKalender' => [],
                'weeks' => [],
                'liburNasional' => [],
                'liburBulanIni' => [],
                'stats' => [
                    'hadir' => 0,
                    'terlambat' => 0,
                    'lembur' => 0,
                ],
            ];
        }

        $start_date = Carbon::parse("$bulan-01")->startOfDay();
        $end_date   = Carbon::parse($bulan)->endOfMonth()->endOfDay();

        $jadwalCollection = DB::table('jadwal')
            ->where('pegawai_nik', $this->pegawaiNik)
            ->whereBetween('tgl', [$start_date->toDateString(), $end_date->toDateString()])
            ->get()
            ->keyBy('tgl');

        $presensiCollection = DB::table('presensi')
            ->where('nik', $this->pegawaiNik)
            ->whereBetween('tgl_presensi', [$start_date->toDateString(), $end_date->toDateString()])
            ->get()
            ->groupBy('tgl_presensi');

        // Ambil data perizinan yang disetujui
        $perizinanCollection = DB::table('pengajuan_izin')
            ->where('nik', $this->pegawaiNik)
            ->where('status_approved', '1') // Hanya yang disetujui
            ->whereBetween('tgl_izin', [$start_date->toDateString(), $end_date->toDateString()])
            ->get()
            ->keyBy('tgl_izin');

        $dataKalender = [];
        $stats = [
            'hadir' => 0,
            'terlambat' => 0,
            'lembur' => 0,
        ];

        $cursor = $start_date->copy();
        while ($cursor->lte($end_date)) {
            $tgl = $cursor->format('Y-m-d');
            $jadwalRow = $jadwalCollection->get($tgl);
            $shift = $jadwalRow->shift ?? '-';

            // Cek apakah ada perizinan untuk tanggal ini
            $perizinanRow = $perizinanCollection->get($tgl);
            $statusKhusus = null;
            $keteranganIzin = null;

            if ($perizinanRow) {
                switch ($perizinanRow->status) {
                    case 'i':
                        $statusKhusus = 'Izin';
                        break;
                    case 's':
                        $statusKhusus = 'Sakit';
                        break;
                    case 'c':
                        $statusKhusus = 'Cuti';
                        break;
                }
                $keteranganIzin = $perizinanRow->keterangan;
            }

            $jamShiftRow = DB::table('kelompokjam')->where('shift', $shift)->first();
            $jammasuk = $jamShiftRow->jammasuk ?? null;
            $jampulang = $jamShiftRow->jampulang ?? null;

            $absenForDate = $presensiCollection->get($tgl) ?? collect();
            $inRecord = $absenForDate->firstWhere('inoutmode', 1);
            $outRecord = $absenForDate->firstWhere('inoutmode', 2);
            $lemburInRecord = $absenForDate->firstWhere('inoutmode', 3);
            $lemburOutRecord = $absenForDate->firstWhere('inoutmode', 4);

            $in = $inRecord->jam_in ?? null;
            $out = $outRecord->jam_in ?? null;
            $lembur_in = $lemburInRecord->jam_in ?? null;
            $lembur_out = $lemburOutRecord->jam_in ?? null;

            // Hitung durasi lembur jika ada
            $lembur_duration = null;
            if (!empty($lembur_in) && !empty($lembur_out)) {
                try {
                    $lemburInTime = Carbon::parse($lembur_in);
                    $lemburOutTime = Carbon::parse($lembur_out);
                    
                    if ($lemburOutTime->lt($lemburInTime)) {
                        $lemburOutTime->addDay();
                    }
                    
                    $lemburSeconds = $lemburInTime->diffInSeconds($lemburOutTime);
                    $lembur_duration = $this->secondsToTime($lemburSeconds);
                } catch (\Exception $e) {
                    $lembur_duration = null;
                }
            }

            $terlambat = 0;
            $terlambat_jam = null;

            // Jika ada perizinan yang disetujui, tidak perlu hitung keterlambatan
            if (!$perizinanRow && $jammasuk && $in && strtolower($shift) !== 'libur') {
                try {
                    $shiftStart = Carbon::parse("$tgl $jammasuk");
                    $shiftEnd   = Carbon::parse("$tgl $jampulang");
                    $inTime     = Carbon::parse("$tgl $in");

                    // shift malam (misal 22:00–06:00)
                    $isNightShift = Carbon::parse($jammasuk)->hour >= 18 && Carbon::parse($jampulang)->hour < 12;
                    if ($isNightShift) {
                        $shiftEnd->addDay();
                        // Tidak terlambat jika masuk sebelum jam masuk malam
                        if (!($inTime->lt($shiftStart) && $inTime->hour >= 18)) {
                            if ($inTime->gt($shiftStart)) {
                                $diffSeconds = $shiftStart->diffInSeconds($inTime);
                                $hours = floor($diffSeconds / 3600);
                                $minutes = floor(($diffSeconds % 3600) / 60);
                                $terlambat = $diffSeconds;
                                $terlambat_jam = sprintf('%02d:%02d', $hours, $minutes);
                            }
                        }
                    } else {
                        if ($inTime->gt($shiftStart)) {
                            $diffSeconds = $shiftStart->diffInSeconds($inTime);
                            $hours = floor($diffSeconds / 3600);
                            $minutes = floor(($diffSeconds % 3600) / 60);
                            $terlambat = $diffSeconds;
                            $terlambat_jam = sprintf('%02d:%02d', $hours, $minutes);
                        }
                    }
                } catch (\Exception $e) {
                    $terlambat = 0;
                    $terlambat_jam = null;
                }
            }

            // Statistik sederhana - tidak hitung jika ada perizinan
            if (!$perizinanRow) {
                if ($in && $out) $stats['hadir']++;
                if ($terlambat > 0) $stats['terlambat']++;
                if ($lembur_in || $lembur_out) $stats['lembur']++;
            }

            $dataKalender[$tgl] = [
                'tgl' => $tgl,
                'shift' => $shift,
                'jam_masuk_shift' => $jammasuk,
                'jam_pulang_shift' => $jampulang,
                'jam_masuk' => $in,
                'jam_pulang' => $out,
                'lembur_in' => $lembur_in,
                'lembur_out' => $lembur_out,
                'lembur_duration' => $lembur_duration,
                'terlambat' => $terlambat,
                'terlambat_jam' => $terlambat_jam,
                'status_khusus' => $statusKhusus,
                'keterangan_izin' => $keteranganIzin,
                'is_perizinan' => !is_null($statusKhusus),
            ];

            $cursor->addDay();
        }

        return [
            'dataKalender' => $dataKalender,
            'weeks'        => $this->generateCalendarWeeks($bulan),
            'liburNasional'=> $this->getNationalHolidays($bulan),
            'liburBulanIni'=> $this->filterHolidaysByMonth($bulan),
            'stats'        => $stats,
        ];
    }

    // === Generate Calendar Weeks ===
    protected function generateCalendarWeeks(string $bulan): array
    {
        $start = Carbon::parse($bulan . '-01')->startOfWeek();
        $end   = Carbon::parse($bulan)->endOfMonth()->endOfWeek();

        $weeks = [];
        $currentWeek = [];

        while ($start <= $end) {
            $currentWeek[] = $start->format('Y-m-d');
            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
            $start->addDay();
        }

        return $weeks;
    }

    // === Utilities ===
    protected function secondsToTime(int $seconds): string
    {
        $sign   = $seconds < 0 ? '-' : '';
        $seconds= abs($seconds);
        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }

    protected function formatTimeFromSeconds(?float $seconds): ?string
    {
        if ($seconds === null) return null;
        $seconds = ((int)$seconds) % 86400;
        return gmdate('H:i', $seconds);
    }

    protected function calculateTimeDifference(?float $actual, ?float $shift): ?string
    {
        if ($actual === null || $shift === null) return null;
        $diff = $actual - $shift;
        $prefix = $diff > 0 ? '+' : ($diff < 0 ? '-' : '±');
        return $prefix . $this->secondsToTime(abs((int)$diff));
    }

    // === Holidays ===
    protected function getNationalHolidays(string $bulan): array
    {
        try {
            $year     = date('Y', strtotime($bulan . '-01'));
            $cacheKey = 'national_holidays_' . $year;

            return cache()->remember($cacheKey, now()->addMonth(), function() use ($year) {
                $response = Http::timeout(3)->get("https://hari-libur-api.vercel.app/api", [
                    'year' => $year
                ]);

                return $response->ok() ? $this->parseHolidayResponse($response->json()) : [];
            });
        } catch (\Exception $e) {
            logger()->error("Libur API error: " . $e->getMessage());
            return [];
        }
    }

    protected function parseHolidayResponse(array $holidays): array
    {
        $result = [];
        foreach ($holidays as $holiday) {
            if ($holiday['is_national_holiday'] ?? false) {
                $result[$holiday['event_date']] = $holiday['event_name'];
            }
        }
        return $result;
    }

    protected function filterHolidaysByMonth(string $bulan): array
    {
        $holidays = $this->getNationalHolidays($bulan);
        $selectedMonth = date('m', strtotime($bulan));

        return array_filter($holidays, function($key) use ($selectedMonth) {
            return date('m', strtotime($key)) == $selectedMonth;
        }, ARRAY_FILTER_USE_KEY);
    }

    // === Statistik ===
    protected function calculateStatistics(array $dataKalender): array
    {
        $stats = $this->initializeStats();

        foreach ($dataKalender as $data) {
            // Skip perhitungan statistik jika ada perizinan
            if ($data['is_perizinan'] ?? false) {
                $this->countPerizinan($stats, $data);
                continue;
            }

            $this->processWorkDayStats($stats, $data);
            $this->processLateStats($stats, $data);
        }

        return $this->calculateAverages($stats);
    }

    protected function initializeStats(): array
    {
        return [
            'terlambat'          => 0,
            'lembur'             => 0,
            'doubleShift'        => 0,
            'workDays'           => 0,
            'izin'               => 0,
            'sakit'              => 0,
            'cuti'               => 0,
            'tugasLuar'          => 0,
            'totalMasukShift'    => 0,
            'totalPulangShift'   => 0,
            'totalMasukActual'   => 0,
            'totalPulangActual'  => 0,
            'countShiftDays'     => 0,
            'totalSelisihMasuk'  => 0,
            'totalSelisihPulang' => 0,
            'avgMasukShift'      => null,
            'avgPulangShift'     => null,
            'avgMasukActual'     => null,
            'avgPulangActual'    => null,
            'avgSelisihMasuk'    => null,
            'avgSelisihPulang'   => null,
            'jumlahTepatWaktu'   => 0,
            'jumlahTerlambat'    => 0,
            'jumlahPulangAwal'   => 0,
            'jumlahPulangLambat' => 0,
        ];
    }

    protected function countPerizinan(array &$stats, array $data): void
    {
        $statusKhusus = strtolower(trim($data['status_khusus'] ?? ''));
        switch ($statusKhusus) {
            case 'izin':
                $stats['izin']++;
                break;
            case 'sakit':
                $stats['sakit']++;
                break;
            case 'cuti':
                $stats['cuti']++;
                break;
        }
    }

    protected function processWorkDayStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk']) || !empty($data['jam_pulang'])) {
            $stats['workDays']++;
        }
    }

    protected function processLateStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk_shift']) && !empty($data['jam_pulang_shift'])) {
            $shiftMasuk = strtotime($data['jam_masuk_shift']);
            $shiftPulang = strtotime($data['jam_pulang_shift']);

            $actualMasuk = !empty($data['jam_masuk']) ? strtotime(strip_tags($data['jam_masuk'])) : null;
            $actualPulang = !empty($data['jam_pulang']) ? strtotime(strip_tags($data['jam_pulang'])) : null;

            $stats['countShiftDays']++;

            if ($actualMasuk) {
                $selisihMasuk = $actualMasuk - $shiftMasuk;
                $stats['totalSelisihMasuk'] += $selisihMasuk;

                if ($selisihMasuk > 60) {
                    $stats['jumlahTerlambat']++;
                    $stats['terlambat'] += $selisihMasuk;
                } elseif ($selisihMasuk <= 60 && $selisihMasuk >= -60) {
                    $stats['jumlahTepatWaktu']++;
                }
            }

            if ($actualPulang) {
                $selisihPulang = $actualPulang - $shiftPulang;
                $stats['totalSelisihPulang'] += $selisihPulang;

                if ($selisihPulang < -60) {
                    $stats['jumlahPulangAwal']++;
                } elseif ($selisihPulang > 60) {
                    $stats['jumlahPulangLambat']++;
                    $stats['lembur'] += $selisihPulang;
                }
            }

            if ($actualMasuk)  $stats['totalMasukActual'] += $actualMasuk;
            if ($actualPulang) $stats['totalPulangActual'] += $actualPulang;

            $stats['totalMasukShift']  += $shiftMasuk;
            $stats['totalPulangShift'] += $shiftPulang;
        }
    }

    protected function calculateAverages(array $stats): array
    {
        if ($stats['countShiftDays'] > 0) {
            $count = $stats['countShiftDays'];
            $stats['avgMasukShift']    = $stats['totalMasukShift'] / $count;
            $stats['avgPulangShift']   = $stats['totalPulangShift'] / $count;
            $stats['avgMasukActual']   = $stats['totalMasukActual'] / max(1, $count);
            $stats['avgPulangActual']  = $stats['totalPulangActual'] / max(1, $count);
            $stats['avgSelisihMasuk']  = $stats['totalSelisihMasuk'] / $count / 60;
            $stats['avgSelisihPulang'] = $stats['totalSelisihPulang'] / $count / 60;
        }
        return $stats;
    }
}