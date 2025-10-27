<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payroll;
use App\Models\UnitKerja;
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

        $query = Payroll::query()->where('periode', $periode);

        if ($unit_id) {
            $query->whereHas('user', function($q) use ($unit_id){
                $q->where('id_unitkerja', $unit_id);
            });
        }

        $data = $query->get()->map(function($r) {
            // Total pendapatan = semua komponen gaji
            $totalPendapatan = ($r->gajipokok ?? 0) 
                              + ($r->pek_tambahan ?? 0)
                              + ($r->masakerja ?? 0)
                              + ($r->komunikasi ?? 0)
                              + ($r->transportasi ?? 0)
                              + ($r->konsumsi ?? 0)
                              + ($r->tunj_asuransi ?? 0)
                              + ($r->jabatan ?? 0);

            // Total potongan = cicilan + asuransi + zakat
            $totalPotongan = ($r->cicilan ?? 0) + ($r->asuransi ?? 0) + ($r->zakat ?? 0);

            $jumlah = $totalPendapatan - $totalPotongan;

            return [
                'id' => $r->id,
                'nik' => $r->nik,
                'nip' => $r->user->nip ?? null,
                'nama' => $r->nama,
                'jmlabsen' => $r->jmlabsen,
                'lembur' => $r->lembur,
                'terlambat' => $r->terlambat,
                'cuti' => $r->cuti,
                'gajipokok' => $r->gajipokok,
                'pek_tambahan' => $r->pek_tambahan,
                'masakerja' => $r->masakerja,
                'komunikasi' => $r->komunikasi,
                'transportasi' => $r->transportasi,
                'konsumsi' => $r->konsumsi,
                'tunj_asuransi' => $r->tunj_asuransi,
                'jabatan' => $r->jabatan,
                'cicilan' => $r->cicilan,
                'asuransi' => $r->asuransi,
                'zakat' => $r->zakat,
                'totalPendapatan' => $totalPendapatan,
                'totalPotongan' => $totalPotongan,
                'jumlah' => $jumlah,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function updateManual(Request $request)
    {
        $data = [$request->field => (float) str_replace(',', '', $request->value)];
        if($request->has('zakat')){
            $data['zakat'] = (float) str_replace(',', '', $request->zakat);
        }

        Payroll::where('nik', $request->nik)
            ->update($data);

        return response()->json(['success' => true]);
    }

public function downloadSlip($payroll_id)
{
    $rekap = Payroll::with('user.unitKerja')->findOrFail($payroll_id);
    
    // Ambil unit kerja user
    $unit = $rekap->user->unitKerja ?? null;

    $setting = [
        'company_name' => $unit->company_name ?? 'Perusahaan',
        'npwp' => $unit->npwp ?? '',
        'alamat' => $unit->alamat ?? '',
        'logo' => $unit->logo ?? '',
        'lokasi' => $unit->lokasi ?? ''
    ];

    $periode = $rekap->periode;

    $pdf = PDF::loadView('hris.payroll.slip_gaji', compact('rekap','setting','periode'))
        ->setPaper([0,0,226.77,600], 'portrait');

    return $pdf->download('SlipGaji_'.$rekap->nama.'_'.date('Ym', strtotime($periode)).'.pdf');
}

}
