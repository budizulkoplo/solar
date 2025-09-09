<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\KonfigBunga;
use App\Models\PinjamanDtl;
use Illuminate\Http\Request;
use App\Models\PinjamanHdr;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobilePinjamanController extends BaseMobileController
{
    // Form pengajuan pinjaman
    public function create()
    {
        $user = Auth::user();
        return view('mobile.pinjaman.create', compact('user'));
    }

    // Simpan pengajuan
    public function store(Request $request)
    {
        $request->validate([
            'nominal_pengajuan' => 'required|numeric',
            'tenor' => 'required|integer',
            'jaminan' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $bunga = KonfigBunga::select('bunga_pinjaman')->first();
        $pinjaman = new PinjamanHdr();
        $pinjaman->id_pinjaman = Str::uuid();
        $pinjaman->tgl_pengajuan = Carbon::now()->format('Y-m-d');
        $pinjaman->nomor_anggota = $user->nomor_anggota;
        $pinjaman->gaji = $user->gaji;
        $pinjaman->nominal_pengajuan = $request->nominal_pengajuan;
        $pinjaman->bunga_pinjaman = $bunga->bunga_pinjaman;
        $pinjaman->tenor = $request->tenor;
        $pinjaman->jaminan = $request->jaminan;
        $pinjaman->status = 'pending';

        $result = DB::select("SELECT hitung_cicilan(?, ?, ?, ?) AS jumlah", [$request->nominal_pengajuan, $bunga->bunga_pinjaman, $request->tenor, 1]);
        $cicilanpertama = $result[0]->jumlah;

        $totalcicilan = PinjamanDtl::where(['nomor_anggota'=>$user->nomor_anggota,'status'=>'hutang'])->sum('total_cicilan');

        $batas = 0.35 * $user->gaji; // 35% dari gaji
        if (($totalcicilan+$cicilanpertama) < $batas) { //PR  hitung hutang yg masih aktif jika < $user->limit_hutang maka lolos
            $pinjaman->VarCicilan = 0; //Cicilan memenuhi syarat (di bawah 35% gaji)
        } else {
            $pinjaman->VarCicilan = 1; //Cicilan terlalu besar (melebihi 35% gaji)
        }
        $pinjaman->save();

        return redirect()->route('mobile.pinjaman.create')->with('success', 'Pengajuan pinjaman berhasil dibuat.');
    }

    // Daftar pengajuan user
    public function index()
    {
        $user = Auth::user();
        $pinjaman = PinjamanHdr::where('nomor_anggota', $user->nomor_anggota)
                    ->orderBy('tgl_pengajuan','desc')->get();
        return view('mobile.pinjaman.index', compact('pinjaman'));
    }
}
