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
                'unitkerja' => optional($p->unitkerja)->namaunit ?? '-',
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

        // Ambil data rekap absensi
        $rekapData = $this->rekapAbsensiData($request)->getData()->data;

        // Hapus data lama periode yang sama
        DB::table('payroll')->where('periode', $periode)->delete();

        // Ambil data hari libur nasional untuk bulan itu
        $bulanStr = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $holidays = $this->filterHolidaysByMonth($bulanStr);
        $holidayDates = array_keys($holidays);

        foreach ($rekapData as $r) {
            // Ambil UMK dari unit kerja pegawai
            $pegawai = User::where('nik', $r->nik)->with('unitkerja')->first();
            $umk = $pegawai?->unitkerja?->umk ?? 0;

            // Hitung Gaji Pokok (Full hadir atau prorata)
            $jadwalHariKerja = Jadwal::where('pegawai_nik', $r->nik)
                ->whereMonth('tgl', $bulan)
                ->whereYear('tgl', $tahun)
                ->where('shift', '<>', 'Libur')
                ->count();

            $hariKerjaAktual = $r->jml_absensi ?? 0;
            $gajiHarian = $umk / 22;
            $gajiPokok = ($hariKerjaAktual >= $jadwalHariKerja)
                ? $umk
                : $gajiHarian * $hariKerjaAktual;

            // Hitung lembur
            $totalJamLembur = 0;
            if (!empty($r->lembur)) {
                [$jam, $menit] = explode(':', $r->lembur);
                $totalJamLembur = $jam + ($menit / 60);
            }
            $upahPerJam = $umk / 173;
            $nominalLembur = $totalJamLembur * $upahPerJam;

            // Hitung kerja di hari libur nasional (HLN)
            $presensiLibur = Presensi::where('nik', $r->nik)
                ->whereIn('tgl_presensi', $holidayDates)
                ->get();

            $totalJamHLN = 0;
            foreach ($presensiLibur as $p) {
                $in = Carbon::parse($p->jam_in);
                $out = Carbon::parse($p->created_at);
                if ($out->lt($in)) $out->addDay();
                $totalJamHLN += $in->diffInHours($out);
            }

            $nominalHLN = $totalJamHLN * $upahPerJam;

            // Insert ke tabel payroll
            DB::table('payroll')->insert([
                'periode'        => $periode,
                'nik'            => $r->nik,
                'nama'           => $r->nama,
                'jmlabsen'       => $r->jml_absensi,
                'lembur'         => $r->lembur,
                'terlambat'      => $r->terlambat,
                'cuti'           => $r->cuti,
                'gaji'           => round($gajiPokok, 2),
                'tunjangan'      => null,
                'nominallembur'  => round($nominalLembur, 2),
                'hln'            => round($nominalHLN, 2),
                'bpjs_kes'       => null,
                'bpjs_tk'        => null,
                'kasbon'         => null,
                'sisakasbon'     => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Data payroll periode $periode berhasil diexport."
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

}
