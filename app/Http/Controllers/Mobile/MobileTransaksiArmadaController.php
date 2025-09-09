<?php
namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Armada;
use App\Models\TransaksiArmada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;

class MobileTransaksiArmadaController extends BaseMobileController
{
    // Form input transaksi
    public function create()
    {
        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

        // Bisa juga kirim list armada kosong, nanti di fill via autocomplete
        $armadas = collect();

        return view('mobile.transaksi_armada.create', compact('drawerMenus', 'armadas', 'user'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'armada_id' => 'required|exists:armadas,id',
            'project_id' => 'required|integer|exists:projects,id',
            'panjang'   => 'required|integer',
            'lebar'     => 'required|integer',
            'tinggi'    => 'required|integer',
            'plus'      => 'nullable|integer',
        ]);

        $volumeCm = $request->panjang * $request->lebar * ($request->tinggi + ($request->plus ?? 0));
        $volumeM3 = round($volumeCm / 1000000, 2);

        \DB::beginTransaction();
        try {
            // Ambil nomor struk terakhir hari ini dengan lock untuk menghindari race-condition
            $lastStruk = TransaksiArmada::whereDate('tgl_transaksi', Carbon::today())
                ->where('project_id', $request->project_id) // counter per project
                ->lockForUpdate()
                ->max('no_struk');

            $noStruk = ($lastStruk ?? 0) + 1;

            $transaksi = TransaksiArmada::create([
                'armada_id'     => $request->armada_id,
                'user_id'       => Auth::id(),
                'project_id'    => $request->project_id,
                'tgl_transaksi' => Carbon::now(),
                'panjang'       => $request->panjang,
                'lebar'         => $request->lebar,
                'tinggi'        => $request->tinggi,
                'plus'          => $request->plus ?? 0,
                'volume'        => $volumeCm, // simpan dalam cm^3
                'no_struk'      => $noStruk,
            ]);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan transaksi: '.$e->getMessage()]);
        }

        // redirect ke halaman print/nota
        return redirect()->route('mobile.transaksi_armada.print', $transaksi->id);
    }

    // Tampilkan halaman print / nota (print-ready HTML)
    public function show($id)
    {
        $transaksi = TransaksiArmada::with(['armada','user','project'])->findOrFail($id);

        // volume dalam m3 (dibulatkan dua desimal)
        $volumeM3 = round(($transaksi->volume ?? 0) / 1000000, 2);

        // QR sebagai SVG (base64) — lebih aman daripada PNG (tidak butuh imagick)
        $qrData = 'transaksi:'.$transaksi->id.'|struk:'.$transaksi->no_struk;
        $qrSvg = base64_encode(
            \QrCode::size(150)
                ->format('svg')
                ->errorCorrection('H')
                ->generate($qrData)
        );

        $project = $transaksi->project ?? \App\Models\Project::find($transaksi->project_id);

        return view('mobile.transaksi_armada.print', [
            'transaksi' => $transaksi,
            'project'   => $project,
            'volumeM3'  => $volumeM3,
            'qr'        => $qrSvg,
        ]);
    }

    public function searchArmada(Request $request)
    {
        $query = $request->get('q', '');
        $queryNormalized = str_replace(' ', '', $query); // hapus spasi untuk pencarian fleksibel

        $armadas = Armada::get()->filter(function($armada) use ($queryNormalized) {
            $nopolNormalized = str_replace(' ', '', $armada->nopol);
            return stripos($nopolNormalized, $queryNormalized) !== false;
        })->values();

        return response()->json($armadas);
    }

    public function history(Request $request)
    {
        $projectId = session('project_id');
        if (!$projectId) {
            return redirect()->route('mobile.home')->withErrors(['error' => 'Project belum dipilih.']);
        }

        // ambil tanggal dari query string 'tgl' (sama dengan name input di blade)
        $tanggal = $request->get('tgl', Carbon::today()->toDateString());

        $transaksis = TransaksiArmada::with(['armada', 'user'])
            ->where('project_id', $projectId)
            ->whereDate('tgl_transaksi', $tanggal)
            ->orderByDesc('tgl_transaksi')
            ->paginate(20);

        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

        // kirim juga $tanggal ke view
        return view('mobile.transaksi_armada.history', compact('transaksis', 'drawerMenus', 'user', 'tanggal'));
    }

    public function edit($id)
    {
        $transaksi = TransaksiArmada::with(['armada','project'])->findOrFail($id);
        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

        return view('mobile.transaksi_armada.edit', compact('transaksi','drawerMenus','user'));
    }

    public function update(Request $request, $id)
    {
        $transaksi = TransaksiArmada::findOrFail($id);

        $request->validate([
            'panjang' => 'required|integer',
            'lebar'   => 'required|integer',
            'tinggi'  => 'required|integer',
            'plus'    => 'nullable|integer',
        ]);

        $volumeCm = $request->panjang * $request->lebar * ($request->tinggi + ($request->plus ?? 0));

        $transaksi->update([
            'panjang' => $request->panjang,
            'lebar'   => $request->lebar,
            'tinggi'  => $request->tinggi,
            'plus'    => $request->plus ?? 0,
            'volume'  => $volumeCm,
        ]);

        return redirect()->route('mobile.transaksi_armada.history')
            ->with('success', 'Transaksi berhasil diupdate.');
    }


}
