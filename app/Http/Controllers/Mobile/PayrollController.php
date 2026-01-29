<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PDF;
use DB;

class PayrollController extends Controller
{

    public function index(Request $request)
{
    $user = Auth::user();

    // Tahun manual: 2024 s/d tahun depan
    $startYear = 2024;
    $endYear   = now()->year + 1;

    $tahunList = collect(range($endYear, $startYear)); // desc

    // Tahun terpilih
    $tahun = $request->get('tahun', now()->year);

    $data = Payroll::where('nik', $user->nik)
        ->whereRaw("LEFT(periode, 4) = ?", [$tahun])
        ->selectRaw("RIGHT(periode, 2) as bulan, MAX(id) as id")
        ->groupBy('bulan')
        ->orderBy('bulan', 'desc')
        ->get();

    return view('mobile.payroll.index', compact('data', 'tahun', 'tahunList'));
}



    public function detail($tahun, $bulan)
    {
        $user = Auth::user();
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        $rekap = Payroll::where('nik', $user->nik)
            ->where('periode', $periode)
            ->firstOrFail();

        $setting = DB::table('setting')->first();
        $pegawai = DB::table('pegawai_dtl')->where('nik', $rekap->nik)->first();
        $unitkerja = DB::table('unitkerja')->where('id', $user->id_unitkerja)->first();
        // $user = \DB::table('users')->where('nik', $rekap->nik)->first();
       
        $setting = \DB::table('setting')->first();

        return view('mobile.payroll.detail', compact('rekap', 'pegawai', 'user', 'unitkerja', 'periode', 'setting'));
    }

}
