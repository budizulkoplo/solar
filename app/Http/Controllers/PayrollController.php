<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PDF; 

class PayrollController extends Controller
{
    public function index()
    {
        $unitkerja = UnitKerja::orderBy('company_name')->get();
        return view('hris.payroll.index', compact('unitkerja'));
    }

    public function getData(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $unit_id = $request->unit_id;

        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        $query = Payroll::query()
            ->select('payroll.*', 'usr.nip', 'usr.name as nama', 'u.company_name')
            ->leftJoin('users as usr', 'usr.nik', '=', 'payroll.nik')
            ->leftJoin('unitkerja as u', 'u.id', '=', 'usr.id_unitkerja')
            ->where('periode', $periode);

        if ($unit_id) {
            $query->where('u.id', $unit_id);
        }

        $data = $query->get();

        return response()->json(['data' => $data]);
    }

    // konversi time H:i ke jam desimal
    private function timeToHours($time)
    {
        [$h, $m] = explode(':', $time);
        return $h + ($m / 60);
    }

    // === fungsi hari libur ===
    protected function getNationalHolidays(string $bulan): array
    {
        try {
            $year = date('Y', strtotime($bulan . '-01'));
            $response = \Http::timeout(5)->get("https://hari-libur-api.vercel.app/api", [
                'year' => $year
            ]);
            return $response->ok() ? $this->parseHolidayResponse($response->json()) : [];
        } catch (\Exception $e) {
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

    public function updateManual(Request $request)
    {
        $value = (float) str_replace(',', '', $request->value); // pastikan numeric
        \DB::table('payroll')
            ->where('nik', $request->nik)
            ->update([$request->field => $value]);

        return response()->json(['success' => true]);
    }

    public function downloadSlip($payroll_id)
    {
        $rekap = Payroll::findOrFail($payroll_id);
        $pegawai = \DB::table('pegawai_dtl')->where('nik', $rekap->nik)->first();
        $user = \DB::table('users')->where('nik', $rekap->nik)->first();
        $unitkerja = \DB::table('unitkerja')->where('id', $user->id_unitkerja)->first();
        $setting = \DB::table('setting')->first();
        $periode = $rekap->periode;

        $pdf = PDF::loadView('hris.payroll.slip_gaji', compact('rekap','user','pegawai','setting','periode','unitkerja'))
        ->setPaper([0, 0, 226.77, 600], 'portrait');
        return $pdf->download('SlipGaji_'.$pegawai->nama.'_'.date('Ym', strtotime($periode)).'.pdf');
    }

}
