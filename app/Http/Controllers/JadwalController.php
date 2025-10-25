<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\KelompokJam;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JadwalController extends Controller
{
    public function index()
    {
        $unitkerja = UnitKerja::orderBy('namaunit')->get();
        $kelompokjam = KelompokJam::orderBy('id')->get();
        return view('master.jadwal.index', compact('unitkerja', 'kelompokjam'));
    }

    public function getPegawai(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $unit_id = $request->unit_id;

        $pegawai = User::where('id_unitkerja', $unit_id)
            ->select('id', 'nik', 'name', 'jabatan')
            ->orderBy('name')
            ->get();

        // Buat daftar tanggal dalam bulan tsb
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $tgl = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $tgl[] = Carbon::create($tahun, $bulan, $d)->toDateString();
        }

        // Ambil jadwal dari DB
        $jadwal = Jadwal::whereMonth('tgl', $bulan)
            ->whereYear('tgl', $tahun)
            ->whereIn('pegawai_nik', $pegawai->pluck('nik'))
            ->get();

        return response()->json([
            'pegawai' => $pegawai,
            'tgl' => $tgl,
            'jadwal' => $jadwal,
        ]);
    }

    public function updateShift(Request $request)
    {
        $request->validate([
            'pegawai_nik' => 'required|string',
            'tgl' => 'required|date',
            'shift' => 'nullable|string|max:100',
        ]);

        Jadwal::updateOrCreate(
            [
                'pegawai_nik' => $request->pegawai_nik,
                'tgl' => $request->tgl,
            ],
            [
                'shift' => $request->shift,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Shift berhasil disimpan.']);
    }

    public function generateOtomatis(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2000',
            'unit_id' => 'required|integer|exists:unitkerja,id',
        ]);

        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $unit_id = $request->unit_id;

        $pegawaiList = User::where('id_unitkerja', $unit_id)
            ->select('nik', 'name')
            ->get();

        // pola default
        $pola = ['Pagi', 'Pagi', 'Siang', 'Siang', 'Malam', 'Malam', 'Libur', 'Libur'];

        DB::beginTransaction();
        try {
            $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;

            foreach ($pegawaiList as $p) {
                // cari shift tanggal 1
                $firstShift = Jadwal::where('pegawai_nik', $p->nik)
                    ->whereMonth('tgl', $bulan)
                    ->whereYear('tgl', $tahun)
                    ->whereDay('tgl', 1)
                    ->value('shift');

                // kalau belum ada shift tgl 1, skip
                if (!$firstShift) {
                    continue;
                }

                // === Tambahan: Jika shift pertama adalah GS, gunakan pola GS ===
                if (strtoupper($firstShift) === 'GS') {
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $tgl = Carbon::create($tahun, $bulan, $d);
                        $hari = $tgl->dayOfWeek; // 0=Min, 1=Sen, 6=Sab

                        $shift = ($hari === 0) ? 'Libur' : 'GS';

                        Jadwal::updateOrCreate(
                            ['pegawai_nik' => $p->nik, 'tgl' => $tgl->toDateString()],
                            ['shift' => $shift]
                        );
                    }
                    continue; // lanjut ke pegawai berikutnya
                }

                // === Pola biasa ===
                $startIndex = array_search($firstShift, $pola);
                if ($startIndex === false) $startIndex = 0;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $tgl = Carbon::create($tahun, $bulan, $d)->toDateString();
                    $shift = $pola[($startIndex + $d - 1) % count($pola)];

                    Jadwal::updateOrCreate(
                        ['pegawai_nik' => $p->nik, 'tgl' => $tgl],
                        ['shift' => $shift]
                    );
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Jadwal otomatis berhasil digenerate.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal generate: ' . $e->getMessage()]);
        }
    }

}
