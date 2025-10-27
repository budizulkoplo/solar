<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payroll;
use Illuminate\Support\Facades\Auth;
use PDF;
use DB;

class MobilePayrollController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tahun = $request->get('tahun', now()->year);

        // Ambil semua tahun unik dari kolom periode (string)
        $tahunList = Payroll::selectRaw("DISTINCT LEFT(periode, 4) as tahun")
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        // Ambil slip berdasarkan NIK dan tahun
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

        // Ambil data payroll dengan relasi user & unitKerja
        $rekap = Payroll::with('user')->where('nik', $user->nik)
            ->where('periode', $periode)
            ->firstOrFail();

        $user = $rekap->user;
        $unitkerja = DB::table('company_units')->where('id', $user->id_unitkerja)->first();

        // Hitung total
        $totalPendapatan = ($rekap->gajipokok ?? 0) 
                          + ($rekap->pek_tambahan ?? 0)
                          + ($rekap->masakerja ?? 0)
                          + ($rekap->komunikasi ?? 0)
                          + ($rekap->transportasi ?? 0)
                          + ($rekap->konsumsi ?? 0)
                          + ($rekap->tunj_asuransi ?? 0)
                          + ($rekap->jabatan ?? 0);

        $totalPotongan = ($rekap->cicilan ?? 0) + ($rekap->asuransi ?? 0) + ($rekap->zakat ?? 0);

        $jumlah = $totalPendapatan - $totalPotongan;

        $setting = [
            'company_name' => $unitkerja->company_name ?? 'Perusahaan',
            'npwp' => $unitkerja->npwp ?? '',
            'alamat' => $unitkerja->alamat ?? '',
            'logo' => $unitkerja->logo ?? '',
            'lokasi' => $unitkerja->lokasi ?? ''
        ];

        return view('mobile.payroll.detail', compact(
            'rekap','user','unitkerja','periode','setting',
            'totalPendapatan','totalPotongan','jumlah'
        ));
    }

    public function slip($payroll_id)
    {
        $rekap = Payroll::with('user')->findOrFail($payroll_id);
        $user = $rekap->user;
        $unit = DB::table('company_units')->where('id', $user->id_unitkerja)->first();

        $setting = [
            'company_name' => $unit->company_name ?? 'Perusahaan',
            'npwp' => $unit->npwp ?? '',
            'alamat' => $unit->alamat ?? '',
            'logo' => $unit->logo ?? '',
            'lokasi' => $unit->lokasi ?? ''
        ];

        $periode = $rekap->periode;

        $pdf = PDF::loadView('hris.payroll.slip_gaji', compact('rekap','user','unit','setting','periode'))
            ->setPaper([0,0,226.77,600], 'portrait');

        return $pdf->download('SlipGaji_'.$rekap->nama.'_'.date('Ym', strtotime($periode)).'.pdf');
    }
}
