<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Presensi;
use App\Models\Jadwal;
use App\Models\KelompokJam;
use App\Models\PengajuanIzin;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; 

class LaporanController extends Controller
{
    // Halaman utama laporan
    public function rekapAbsensi()
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        return view('hris.laporan.rekap_absensi', compact('bulan', 'tahun'));
    }

    // Data untuk DataTables (AJAX)
    public function rekapAbsensiData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
        $akhir = $awal->copy()->endOfMonth();

        $pegawaiList = User::with('unitkerja')
        ->where('status', 'aktif')                // hanya pegawai aktif
        ->whereHas('pegawaiDtl')                  // hanya yang punya detail pegawai
        ->get();
        $data = [];

        foreach ($pegawaiList as $p) {
            $jadwalCollection = Jadwal::where('pegawai_nik', $p->nik)
                ->whereBetween('tgl', [$awal, $akhir])
                ->get()
                ->keyBy('tgl');

            $presensiCollection = Presensi::where('nik', $p->nik)
                ->whereBetween('tgl_presensi', [$awal, $akhir])
                ->get()
                ->groupBy('tgl_presensi');

            $cutiCount = PengajuanIzin::where('nik', $p->nik)
                ->whereMonth('tgl_izin', $bulan)
                ->whereYear('tgl_izin', $tahun)
                ->where('status', 'c')
                ->where('status_approved', 1)
                ->count();

            $jmlAbsensi = 0;
            $totalTerlambatSeconds = 0;
            $totalLemburSeconds = 0;

            $cursor = $awal->copy();
            while ($cursor->lte($akhir)) {
                $tgl = $cursor->format('Y-m-d');
                $jadwalRow = $jadwalCollection->get($tgl);
                $shift = $jadwalRow->shift ?? null;
                $jam = KelompokJam::firstWhere('shift', $shift);
                $jammasuk = $jam->jammasuk ?? null;

                $absensiHari = $presensiCollection->get($tgl) ?? collect();
                $in = optional($absensiHari->firstWhere('inoutmode', 1))->jam_in;

                if ($in) $jmlAbsensi++;

                if ($jammasuk && $in && strtolower($shift) !== 'libur') {
                    $shiftStart = Carbon::parse("$tgl $jammasuk");
                    $inDt = Carbon::parse("$tgl $in");
                    if ($inDt->gt($shiftStart)) {
                        $totalTerlambatSeconds += $shiftStart->diffInSeconds($inDt);
                    }
                }

                $lemburIn = optional($absensiHari->firstWhere('inoutmode', 3))->jam_in;
                $lemburOut = optional($absensiHari->firstWhere('inoutmode', 4))->jam_in;
                if ($lemburIn && $lemburOut) {
                    $inDt = Carbon::parse("$tgl $lemburIn");
                    $outDt = Carbon::parse("$tgl $lemburOut");
                    if ($outDt->lt($inDt)) $outDt->addDay();
                    $totalLemburSeconds += $inDt->diffInSeconds($outDt);
                }

                $cursor->addDay();
            }

            $data[] = [
                'nik' => $p->nik,
                'nama' => $p->name,
                'unitkerja' => optional($p->unitkerja)->company_name ?? '-',
                'jml_absensi' => $jmlAbsensi,
                'lembur' => gmdate('H:i', $totalLemburSeconds),
                'terlambat' => gmdate('H:i', $totalTerlambatSeconds),
                'cuti' => $cutiCount,
                'total' => $jmlAbsensi + $cutiCount
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function exportPayroll(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        if (!$bulan || !$tahun) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter bulan dan tahun wajib diisi.'
            ]);
        }

        // Ambil data rekap absensi
        $rekapData = collect($this->rekapAbsensiData($request)->getData()->data);

        // Ambil semua pegawai aktif
        $pegawaiList = User::where('status', 'aktif')->get();

        if ($pegawaiList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "Tidak ada pegawai aktif untuk periode $periode."
            ]);
        }

        // Hapus payroll lama periode yang sama
        DB::table('payroll')->where('periode', $periode)->delete();

        $inserted = 0;
        $debugLog = [];

        foreach ($pegawaiList as $p) {
            // Ambil mastergaji terakhir per NIK
            $gaji = DB::table('mastergaji')
                ->where('nik', $p->nik)
                ->whereNull('deleted_at')
                ->orderByDesc('tgl_aktif')
                ->first();

            if (!$gaji) {
                $debugLog[] = "Mastergaji untuk NIK {$p->nik} tidak ditemukan, dilewati.";
                continue;
            }

            // Ambil data rekap absensi per pegawai
            $rekap = $rekapData->firstWhere('nik', $p->nik);

            $jmlabsen  = $rekap->jml_absensi ?? 0;
            $lembur    = $rekap->lembur ?? '00:00:00';
            $terlambat = $rekap->terlambat ?? '00:00:00';
            $cuti      = $rekap->cuti ?? 0;

            // Hitung total pendapatan
            $totalPendapatan = ($gaji->gajipokok ?? 0)
                            + ($gaji->masakerja ?? 0)
                            + ($gaji->komunikasi ?? 0)
                            + ($gaji->transportasi ?? 0)
                            + ($gaji->konsumsi ?? 0)
                            + ($gaji->tunj_asuransi ?? 0)
                            + ($gaji->jabatan ?? 0)
                            + ($gaji->pek_tambahan ?? 0);

            // Hitung zakat 2.5%
            $zakat = round($totalPendapatan * 0.025, 2);

            // Siapkan data insert payroll
            $data = [
                'periode'       => $periode,
                'nik'           => $p->nik,
                'nama'          => $p->name,
                'jmlabsen'      => $jmlabsen,
                'lembur'        => $lembur,
                'terlambat'     => $terlambat,
                'cuti'          => $cuti,
                'gajipokok'     => round($gaji->gajipokok ?? 0, 2),
                'pek_tambahan'  => round($gaji->pek_tambahan ?? 0, 2),
                'masakerja'     => round($gaji->masakerja ?? 0, 2),
                'komunikasi'    => round($gaji->komunikasi ?? 0, 2),
                'transportasi'  => round($gaji->transportasi ?? 0, 2),
                'konsumsi'      => round($gaji->konsumsi ?? 0, 2),
                'tunj_asuransi' => round($gaji->tunj_asuransi ?? 0, 2),
                'jabatan'       => round($gaji->jabatan ?? 0, 2),
                'cicilan'       => 0,
                'asuransi'      => round($gaji->asuransi ?? 0, 2),
                'zakat'         => $zakat,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            try {
                DB::table('payroll')->insert($data);
                $inserted++;
            } catch (\Exception $e) {
                $debugLog[] = "Gagal insert NIK {$p->nik}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Data payroll periode $periode berhasil diexport. ($inserted pegawai)",
            'log'     => $debugLog,
        ]);
    }

    // === Holidays ===
    protected function getNationalHolidays(string $bulan): array
    {
        try {
            $year = date('Y', strtotime($bulan . '-01'));
            $cacheKey = 'national_holidays_' . $year;

            return cache()->remember($cacheKey, now()->addMonth(), function () use ($year) {
                $response = Http::timeout(5)->get("https://hari-libur-api.vercel.app/api", [
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
            if (($holiday['is_national_holiday'] ?? false) === true) {
                $result[$holiday['event_date']] = $holiday['event_name'];
            }
        }
        return $result;
    }

    protected function filterHolidaysByMonth(string $bulan): array
    {
        $holidays = $this->getNationalHolidays($bulan);
        $selectedMonth = date('m', strtotime($bulan));

        return array_filter($holidays, function ($key) use ($selectedMonth) {
            return date('m', strtotime($key)) == $selectedMonth;
        }, ARRAY_FILTER_USE_KEY);
    }

    // ==========================
    // LAPORAN PAYROLL
    // ==========================
    public function laporanPayroll()
    {
        $bulan = now()->format('m');
        $tahun = now()->format('Y');
        return view('hris.laporan.laporan_payroll', compact('bulan', 'tahun'));
    }

    public function laporanPayrollData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        $data = DB::table('payroll')
            ->where('periode', $periode)
            ->join('users', 'payroll.nik', '=', 'users.nik')
            ->leftJoin('company_units', 'users.id_unitkerja', '=', 'company_units.id')
            ->select(
                'payroll.*','users.nip as nip',
                'users.name as nama',
                'company_units.company_name as unitkerja'
            )
            ->orderBy('users.name')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function monitoringPresensi()
    {
        return view('hris.laporan.monitoring_presensi');
    }

    public function monitoringPresensiData(Request $request)
    {
        $tanggal = $request->tanggal ?? date('Y-m-d');

        $data = DB::table('presensi as p')
            ->select(
                'p.nik',
                'k.nip',
                'k.name',
                'u.company_name',
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.jam_in END) as jam_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.jam_in END) as jam_pulang'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.foto_in END) as foto_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.foto_in END) as foto_pulang'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 1 THEN p.lokasi END) as lokasi_masuk'),
                DB::raw('MAX(CASE WHEN p.inoutmode = 2 THEN p.lokasi END) as lokasi_pulang')
            )
            ->join('users as k', 'p.nik', '=', 'k.nik')
            ->leftJoin('company_units as u', 'k.id_unitkerja', '=', 'u.id')
            ->where('p.tgl_presensi', $tanggal)
            ->groupBy('k.nip','p.nik', 'k.name', 'u.company_name')
            ->orderBy('k.name')
            ->get();

        // ✅ Ini yang benar untuk DataTables
        return response()->json(['data' => $data]);
    }
}
