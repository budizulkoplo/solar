<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bonus; 
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;
use Illuminate\Support\Str; 

class MobileBonusController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $tahun = $request->get('tahun', now()->year);

        // Ambil semua tahun unik dari periode bonus
        $tahunList = Bonus::selectRaw("DISTINCT LEFT(periode, 4) as tahun")
            ->where('nik', $user->nik)
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        // Jika user belum punya bonus, tambahkan tahun sekarang
        if ($tahunList->isEmpty()) {
            $tahunList = collect([now()->year]);
        }

        // Ambil periode bonus unik untuk tahun yang dipilih
        $data = Bonus::where('nik', $user->nik)
            ->whereRaw("LEFT(periode, 4) = ?", [$tahun])
            ->selectRaw("periode, 
                        MIN(id) as id, 
                        SUM(nominal) as total_nominal, 
                        COUNT(*) as jumlah_bonus,
                        MAX(created_at) as last_created")
            ->groupBy('periode')
            ->orderBy('periode', 'desc')
            ->get();

        return view('mobile.bonus.index', compact('data', 'tahun', 'tahunList'));
    }

    public function detail($periode)
    {
        $user = Auth::user();
        
        // Validasi format periode
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            abort(404, 'Format periode tidak valid');
        }
        
        // Ambil semua bonus untuk periode tertentu
        $bonuses = Bonus::where('nik', $user->nik)
            ->where('periode', $periode)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($bonuses->isEmpty()) {
            abort(404, 'Data bonus tidak ditemukan');
        }

        $totalBonus = $bonuses->sum('nominal');
        
        // Ambil data user dan unit kerja
        $unitkerja = DB::table('company_units')->where('id', $user->id_unitkerja)->first();

        $setting = [
            'company_name' => $unitkerja->company_name ?? 'Perusahaan',
            'npwp' => $unitkerja->npwp ?? '',
            'alamat' => $unitkerja->alamat ?? '',
            'logo' => $unitkerja->logo ?? '',
            'lokasi' => $unitkerja->lokasi ?? ''
        ];

        return view('mobile.bonus.detail', compact(
            'bonuses', 'user', 'unitkerja', 'periode', 'setting', 'totalBonus'
        ));
    }

    public function slip($periode)
    {
        $user = Auth::user();
        
        // Validasi format periode
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            abort(404, 'Format periode tidak valid');
        }
        
        // Ambil semua bonus untuk periode tertentu
        $bonuses = Bonus::where('nik', $user->nik)
            ->where('periode', $periode)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($bonuses->isEmpty()) {
            abort(404, 'Data bonus tidak ditemukan');
        }

        $totalBonus = $bonuses->sum('nominal');
        
        // Ambil data unit kerja
        $unitkerja = DB::table('company_units')->where('id', $user->id_unitkerja)->first();

        $setting = [
            'company_name' => $unitkerja->company_name ?? 'Perusahaan',
            'npwp' => $unitkerja->npwp ?? '',
            'alamat' => $unitkerja->alamat ?? '',
            'logo' => $unitkerja->logo ?? '',
            'lokasi' => $unitkerja->lokasi ?? ''
        ];

        // Generate nama file
        $bulanTahun = date('Ym', strtotime($periode));
        $fileName = 'SlipBonus_' . $user->name . '_' . $bulanTahun . '.pdf';

        $pdf = PDF::loadView('mobile.bonus.slip_pdf', compact(
            'bonuses', 'user', 'unitkerja', 'periode', 'setting', 'totalBonus'
        ))->setPaper([0,0,226.77,600], 'portrait');

        return $pdf->download($fileName);
    }
}