<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bonus;
use App\Models\UnitKerja;
use App\Models\User;
use PDF;

class BonusController extends Controller
{
    public function index()
    {
        $unitkerja = UnitKerja::orderBy('company_name')->get();
        return view('hris.bonus.index', compact('unitkerja'));
    }

    public function getData(Request $request)
    {
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $unit_id = $request->unit_id;

        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        // Ambil semua user berdasarkan filter unit
        $query = User::query()->where('status', 'aktif');
        
        if ($unit_id) {
            $query->where('id_unitkerja', $unit_id);
        }

        $users = $query->orderBy('name')->get();

        $data = $users->map(function($user) use ($periode) {
            // Ambil semua bonus untuk user ini di periode tertentu
            $bonuses = Bonus::where('periode', $periode)
                          ->where('nik', $user->nik)
                          ->orderBy('created_at')
                          ->get();

            $totalBonus = $bonuses->sum('nominal');
            
            return [
                'id' => $user->id,
                'nik' => $user->nik,
                'nip' => $user->nip,
                'nama' => $user->name,
                'unit_kerja' => $user->unitKerja->company_name ?? null,
                'bonuses' => $bonuses->map(function($bonus) {
                    return [
                        'id' => $bonus->id,
                        'keterangan' => $bonus->keterangan,
                        'nominal' => $bonus->nominal,
                        'created_at' => $bonus->created_at->format('d/m/Y H:i')
                    ];
                })->toArray(),
                'total_bonus' => $totalBonus,
                'bonus_count' => $bonuses->count()
            ];
        });

        return response()->json(['data' => $data, 'periode' => $periode]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required',
            'nik' => 'required',
            'nominal' => 'required|numeric',
            'keterangan' => 'required|max:255'
        ]);

        $user = User::where('nik', $request->nik)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'NIK tidak ditemukan']);
        }

        $bonus = Bonus::create([
            'periode' => $request->periode,
            'nik' => $request->nik,
            'nama' => $user->name,
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan
        ]);

        return response()->json(['success' => true, 'data' => $bonus]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nominal' => 'required|numeric',
            'keterangan' => 'required|max:255'
        ]);

        $bonus = Bonus::findOrFail($id);
        $bonus->update([
            'nominal' => $request->nominal,
            'keterangan' => $request->keterangan
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $bonus = Bonus::findOrFail($id);
        $bonus->delete();

        return response()->json(['success' => true]);
    }

    public function downloadSlip($nik, $periode)
    {
        $user = User::with('unitKerja')->where('nik', $nik)->firstOrFail();
        
        // Ambil semua bonus untuk periode ini
        $bonuses = Bonus::where('periode', $periode)
                       ->where('nik', $nik)
                       ->orderBy('created_at')
                       ->get();
        
        $totalBonus = $bonuses->sum('nominal');

        $unit = $user->unitKerja ?? null;

        $setting = [
            'company_name' => $unit->company_name ?? 'Perusahaan',
            'npwp' => $unit->npwp ?? '',
            'alamat' => $unit->alamat ?? '',
            'logo' => $unit->logo ?? '',
            'lokasi' => $unit->lokasi ?? ''
        ];

        $pdf = PDF::loadView('hris.bonus.slip_bonus', compact('user', 'bonuses', 'totalBonus', 'setting', 'periode'))
            ->setPaper([0,0,226.77,600], 'portrait');

        return $pdf->download('SlipBonus_'.$user->name.'_'.date('Ym', strtotime($periode)).'.pdf');
    }

    public function getBonusByUser(Request $request)
    {
        $bonuses = Bonus::where('periode', $request->periode)
                       ->where('nik', $request->nik)
                       ->orderBy('created_at')
                       ->get();

        return response()->json(['bonuses' => $bonuses]);
    }
}