<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Jadwal;
use App\Models\Presensi;
use App\Models\KelompokJam;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $bulan = date('m');
        $tahun = date('Y');
        return view('hris.absensi.index', compact('bulan', 'tahun'));
    }

    public function getAbsensiData(Request $request)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        try {
            $awal = Carbon::createFromDate($tahun, $bulan, 1)->startOfDay();
            $akhir = $awal->copy()->endOfMonth();
        } catch (\Exception $e) {
            $awal = now()->startOfMonth();
            $akhir = now()->endOfMonth();
        }

        $karyawan = User::with('unitkerja')->get();
        $data = [];

        foreach ($karyawan as $user) {
            $jadwalCollection = Jadwal::where('pegawai_nik', $user->nik)
                ->whereBetween('tgl', [$awal->toDateString(), $akhir->toDateString()])
                ->get()
                ->keyBy('tgl');

            $presensiCollection = Presensi::where('nik', $user->nik)
                ->whereBetween('tgl_presensi', [$awal->toDateString(), $akhir->toDateString()])
                ->get()
                ->groupBy('tgl_presensi');

            // 🔹 Ambil data izin (hanya yg disetujui)
            $izinCollection = \DB::table('pengajuan_izin')
                ->where('nik', $user->nik)
                ->where('status_approved', 1)
                ->whereBetween('tgl_izin', [$awal->toDateString(), $akhir->toDateString()])
                ->get()
                ->keyBy('tgl_izin');

            $hari = [];
            $total_terlambat = 0;

            $cursor = $awal->copy();
            while ($cursor->lte($akhir)) {
                $tgl = $cursor->format('Y-m-d');
                $jadwalRow = $jadwalCollection->get($tgl);
                $shift = $jadwalRow->shift ?? '-';

                // 🔹 Cek apakah tanggal ini ada izin
                $izin = $izinCollection->get($tgl);
                $status_izin = '';
                $keterangan_izin = '';

                if ($izin) {
                    // konversi kode status ke huruf besar & keterangan
                    switch ($izin->status) {
                        case 'i':
                            $status_izin = 'IZIN';
                            break;
                        case 's':
                            $status_izin = 'SAKIT';
                            break;
                        case 'c':
                            $status_izin = 'CUTI';
                            break;
                    }
                    $keterangan_izin = $izin->keterangan ?? '';
                }

                // ambil jam masuk & pulang dari tabel kelompokjam
                $jam = KelompokJam::firstWhere('shift', $shift);
                $jammasuk = $jam->jammasuk ?? null;
                $jampulang = $jam->jampulang ?? null;

                $absenForDate = $presensiCollection->get($tgl) ?? collect();

                $inRecord = $absenForDate->firstWhere('inoutmode', 1);
                $outRecord = $absenForDate->firstWhere('inoutmode', 2);
                $lemburInRecord = $absenForDate->firstWhere('inoutmode', 3);
                $lemburOutRecord = $absenForDate->firstWhere('inoutmode', 4);

                $in = $inRecord->jam_in ?? '';
                $out = $outRecord->jam_in ?? '';
                $lembur_in = $lemburInRecord->jam_in ?? '';
                $lembur_out = $lemburOutRecord->jam_in ?? '';

                $terlambat = 0;
                $terlambat_jam = '00:00:00';

                // 🔹 Jika ada izin disetujui → abaikan perhitungan presensi
                if ($izin) {
                    $hari[] = [
                        'tgl' => $tgl,
                        'shift' => $shift,
                        'status_izin' => $status_izin,
                        'keterangan_izin' => $keterangan_izin,
                        'in' => '',
                        'out' => '',
                        'lembur_in' => '',
                        'lembur_out' => '',
                        'terlambat' => 0,
                        'terlambat_jam' => '00:00:00',
                    ];
                    $cursor->addDay();
                    continue;
                }

                // 🔹 Perhitungan keterlambatan hanya kalau tidak izin
                if ($jammasuk && $in && strtolower($shift) !== 'libur') {
                    try {
                        $shiftStart = Carbon::parse("$tgl $jammasuk");
                        $shiftEnd = Carbon::parse("$tgl $jampulang");

                        if ($shiftEnd->lessThan($shiftStart)) {
                            $shiftEnd->addDay();
                        }

                        $inDt = Carbon::parse("$tgl $in");
                        if (strtolower($shift) === 'malam' && $inDt->hour < 12) {
                            $inDt->addDay();
                        }

                        if ($inDt->greaterThan($shiftStart)) {
                            $diffSeconds = $shiftStart->diffInSeconds($inDt);
                            $terlambat = round($diffSeconds / 60, 2);
                            $total_terlambat += $terlambat;

                            $hours = floor($diffSeconds / 3600);
                            $minutes = floor(($diffSeconds % 3600) / 60);
                            $seconds = $diffSeconds % 60;
                            $terlambat_jam = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                        }
                    } catch (\Exception $e) {
                        $terlambat = 0;
                        $terlambat_jam = '00:00:00';
                    }
                }

                $hari[] = [
                    'tgl' => $tgl,
                    'shift' => $shift,
                    'status_izin' => '',
                    'keterangan_izin' => '',
                    'in' => $in,
                    'out' => $out,
                    'lembur_in' => $lembur_in,
                    'lembur_out' => $lembur_out,
                    'terlambat' => $terlambat,
                    'terlambat_jam' => $terlambat_jam,
                ];

                $cursor->addDay();
            }

            $data[] = [
                'nama' => $user->name,
                'unitkerja' => optional($user->unitkerja)->company_name ?? '-',
                'hari' => $hari,
                'total_terlambat' => $total_terlambat,
            ];
        }

        return response()->json([
            'data' => $data,
            'recordsTotal' => count($data),
            'recordsFiltered' => count($data),
            'draw' => intval($request->input('draw'))
        ]);
    }


}
