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

    protected function initializeEmployeeData(): void
        {
            
            $this->middleware(function ($request, $next) {
            $this->user = auth()->user(); // pastikan user login
            $this->pegawaiNik = $this->user ? $this->user->nik : null;
            return $next($request);
        });

        $this->employees = DB::table('users')
            ->select('nik', 'name')
            ->where('status', '<>', 'nonaktif')
            ->orderBy('name')
            ->get();

        $this->selectedEmployee = $this->employees->firstWhere('nik', $this->pegawaiNik);
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
            // Ambil data kalender (sudah termasuk jam_masuk_shift & jam_pulang_shift dari SP)
            $data = $this->prepareKalenderData($bulan);

            foreach ($data['dataKalender'] as $tgl => &$row) {
                // Normalisasi shift name
                $shiftName = strtolower(trim($row['shift'] ?? ''));

                // Jika SP belum kirim jam shift, isi default hanya kalau Office
                if ($shiftName === 'office') {
                    $row['jam_masuk_shift']  = $row['jam_masuk_shift']  ?? '08:00:00';
                    $row['jam_pulang_shift'] = $row['jam_pulang_shift'] ?? '16:00:00';
                }

                // Normalisasi status_khusus
                if (!empty($row['status_khusus'])) {
                    $row['status_khusus'] = trim(strip_tags($row['status_khusus']));
                }
            }
            unset($row);

            // Hitung statistik dasar
            $stats = $this->calculateStatistics($data['dataKalender']);

            // Range periode 26 bulan lalu s.d 25 bulan ini
            $startPeriode     = Carbon::parse($bulan . '-26')->subMonth();
            $endPeriode       = Carbon::parse($bulan . '-25');
            $totalHariPeriode = $startPeriode->diffInDays($endPeriode) + 1;

            // --- Grafik keterlambatan ---
            $chartLabels = [];
            $chartValues = [];

            foreach ($data['dataKalender'] as $tgl => $row) {
                $chartLabels[] = Carbon::parse($tgl)->translatedFormat('d M');

                if (!empty($row['jam_masuk']) && !empty($row['jam_masuk_shift'])) {
                    $masuk = strtotime(strip_tags($row['jam_masuk']));
                    $shift = strtotime($row['jam_masuk_shift']);
                    $chartValues[] = max(0, round(($masuk - $shift) / 60, 1)); // menit keterlambatan
                } else {
                    $chartValues[] = 0;
                }
            }

            // Normalisasi statistik kosong
            $stats = array_merge([
                'jumlahTepatWaktu'   => 0,
                'jumlahTerlambat'    => 0,
                'jumlahPulangAwal'   => 0,
                'jumlahPulangLambat' => 0,
            ], $stats);

            // Hitung cuti dari status_khusus = 'Cuti'
            $jumlahCuti = collect($data['dataKalender'])
                ->filter(fn($item) => isset($item['status_khusus']) && strtolower(trim($item['status_khusus'])) === 'cuti')
                ->count();

            // Gabungkan semua ke viewData
            $viewData = array_merge($viewData, [
                'selectedEmployee'   => $this->selectedEmployee,
                'dataKalender'       => $data['dataKalender'],
                'terlambatFormatted' => $this->secondsToTime($stats['terlambat'] ?? 0),
                'lemburFormatted'    => $this->secondsToTime($stats['lembur'] ?? 0),
                'totalWorkDays'      => $stats['workDays'] ?? 0,
                'totalCuti'          => $stats['cuti'] ?? 0,
                'totalTugasLuar'     => $stats['tugasLuar'] ?? 0,
                'doubleShift'        => $stats['doubleShift'] ?? 0,
                'avgMasukShift'      => $this->formatTimeFromSeconds($stats['avgMasukShift'] ?? null),
                'avgPulangShift'     => $this->formatTimeFromSeconds($stats['avgPulangShift'] ?? null),
                'avgMasukActual'     => $this->formatTimeFromSeconds($stats['avgMasukActual'] ?? null),
                'avgPulangActual'    => $this->formatTimeFromSeconds($stats['avgPulangActual'] ?? null),
                'avgSelisihMasuk'    => $stats['avgSelisihMasuk'] ?? null,
                'avgSelisihPulang'   => $stats['avgSelisihPulang'] ?? null,
                'diffMasuk'          => $this->calculateTimeDifference(
                                            $stats['avgMasukActual'] ?? null,
                                            $stats['avgMasukShift'] ?? null
                                        ),
                'diffPulang'         => $this->calculateTimeDifference(
                                            $stats['avgPulangActual'] ?? null,
                                            $stats['avgPulangShift'] ?? null
                                        ),
                'countShiftDays'     => $stats['countShiftDays'] ?? 0,
                'jumlahTepatWaktu'   => $stats['jumlahTepatWaktu'],
                'jumlahTerlambat'    => $stats['jumlahTerlambat'],
                'jumlahPulangAwal'   => $stats['jumlahPulangAwal'],
                'jumlahPulangLambat' => $stats['jumlahPulangLambat'],
                'jumlahCuti'         => $jumlahCuti,
                'totalHariPeriode'   => $totalHariPeriode,
                'chartLabels'        => $chartLabels,
                'chartValues'        => $chartValues,
            ]);
        }

        return view('mobile.kalender.statistik', $viewData);
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
                'totalTerlambatSeconds'=> $stats['terlambat'],
                'totalLemburSeconds'   => $stats['lembur'],
                'totalWorkDays'        => $stats['workDays'],
                'totalCuti'            => $stats['cuti'],
                'totalTugasLuar'       => $stats['tugasLuar'],
            ]);
        }

        return view($view, $viewData);
    }

    protected function validateMonth(string $month): string
    {
        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
    }

    protected function prepareKalenderData(string $bulan): array
    {
        if (!$this->pegawaiNik) {
            return [
                'dataKalender' => [],
                'weeks' => [],
                'liburNasional' => [],
                'liburBulanIni' => [],
            ];
        }

        $start_date = Carbon::parse($bulan . '-26')->subMonth()->format('Y-m-d');
        $end_date   = Carbon::parse($bulan . '-25')->format('Y-m-d');

        $result = DB::select("CALL spKalenderAbsensiPegawai(?, ?, ?)", [
            $this->pegawaiNik, $start_date, $end_date
        ]);

        $dataKalender = [];
        foreach ($result as $row) {
            $dataKalender[$row->tgl] = (array) $row;
        }

        return [
            'dataKalender' => $dataKalender,
            'weeks'        => $this->generateCalendarWeeks($bulan),
            'liburNasional'=> $this->getNationalHolidays($bulan),
            'liburBulanIni'=> $this->filterHolidaysByMonth($bulan),
        ];
    }

    protected function generateCalendarWeeks(string $bulan): array
    {
        $start = Carbon::parse($bulan . '-26')->subMonth()->startOfWeek();
        $end   = Carbon::parse($bulan . '-25')->endOfWeek()->addDay();

        $weeks = [];
        $currentWeek = [];

        while ($start < $end) {
            $currentWeek[] = $start->format('Y-m-d');
            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
            $start->addDay();
        }

        return $weeks;
    }

    // tampilkan total jam (>=24 jam tidak dipotong)
    protected function secondsToTime(int $seconds): string
    {
        $sign   = $seconds < 0 ? '-' : '';
        $seconds= abs($seconds);

        $hours   = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%s%d:%02d', $sign, $hours, $minutes);
    }

    // untuk rata-rata jam (selalu 0–23 jam)
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
            if ($holiday['is_national_holiday']) {
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

    protected function calculateStatistics(array $dataKalender): array
    {
        $stats = $this->initializeStats();

        foreach ($dataKalender as $data) {
            $this->processWorkDayStats($stats, $data);
            $this->processLateStats($stats, $data);
        }

        return $this->calculateAverages($stats);
    }
    
    protected function calculateAverages(array $stats): array
    {
        if ($stats['countShiftDays'] > 0) {
            $count = $stats['countShiftDays'];

            $stats['avgMasukShift']    = $stats['totalMasukShift'] / $count;
            $stats['avgPulangShift']   = $stats['totalPulangShift'] / $count;
            $stats['avgMasukActual']   = $stats['totalMasukActual'] / max(1, $count);
            $stats['avgPulangActual']  = $stats['totalPulangActual'] / max(1, $count);
            $stats['avgSelisihMasuk']  = $stats['totalSelisihMasuk'] / $count / 60;   // hasil dalam menit
            $stats['avgSelisihPulang'] = $stats['totalSelisihPulang'] / $count / 60;  // hasil dalam menit
        } else {
            $stats['avgMasukShift'] = $stats['avgPulangShift'] = null;
            $stats['avgMasukActual'] = $stats['avgPulangActual'] = null;
            $stats['avgSelisihMasuk'] = $stats['avgSelisihPulang'] = null;
        }

        return $stats;
    }


    protected function initializeStats(): array
    {
        return [
            'terlambat'          => 0,
            'lembur'             => 0,
            'doubleShift'        => 0,
            'workDays'           => 0,
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

    protected function processWorkDayStats(array &$stats, array $data): void
    {
        if (!empty($data['jam_masuk']) || !empty($data['jam_pulang'])) {
            $stats['workDays']++;
        }
    }

    protected function processLateStats(array &$stats, array $data): void
    {
        // Hanya hitung jika ada data shift dan jam actual
        if (!empty($data['jam_masuk_shift']) && !empty($data['jam_pulang_shift'])) {
            $shiftMasuk = strtotime($data['jam_masuk_shift']);
            $shiftPulang = strtotime($data['jam_pulang_shift']);

            $actualMasuk = !empty($data['jam_masuk']) ? strtotime(strip_tags($data['jam_masuk'])) : null;
            $actualPulang = !empty($data['jam_pulang']) ? strtotime(strip_tags($data['jam_pulang'])) : null;

            // Tambah counter hari shift aktif
            $stats['countShiftDays']++;

            // --- Selisih Masuk ---
            if ($actualMasuk) {
                $selisihMasuk = $actualMasuk - $shiftMasuk;
                $stats['totalSelisihMasuk'] += $selisihMasuk;

                // Hitung terlambat atau tepat waktu
                if ($selisihMasuk > 60) { // lebih dari 1 menit
                    $stats['jumlahTerlambat']++;
                    $stats['terlambat'] += $selisihMasuk;
                } elseif ($selisihMasuk <= 60 && $selisihMasuk >= -60) {
                    $stats['jumlahTepatWaktu']++;
                }
            }

            // --- Selisih Pulang ---
            if ($actualPulang) {
                $selisihPulang = $actualPulang - $shiftPulang;
                $stats['totalSelisihPulang'] += $selisihPulang;

                // Hitung pulang awal / lambat
                if ($selisihPulang < -60) {
                    $stats['jumlahPulangAwal']++;
                } elseif ($selisihPulang > 60) {
                    $stats['jumlahPulangLambat']++;
                    $stats['lembur'] += $selisihPulang;
                }
            }

            // Tambahkan ke total jam untuk rata-rata
            if ($actualMasuk)  $stats['totalMasukActual'] += $actualMasuk;
            if ($actualPulang) $stats['totalPulangActual'] += $actualPulang;

            $stats['totalMasukShift']  += $shiftMasuk;
            $stats['totalPulangShift'] += $shiftPulang;
        }
    }



}
