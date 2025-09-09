<?php
namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Armada;
use App\Models\TransaksiArmada;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MobileTransaksiArmadaController extends BaseMobileController
{
    // Format Nopol seragam: H 1234 ABC
    private function formatNopol($nopol)
    {
        $nopol = strtoupper(trim($nopol));

        // Pola umum: huruf depan, angka, huruf belakang
        if (preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $nopol, $m)) {
            return $m[1] . ' ' . $m[2] . ' ' . $m[3];
        }

        return $nopol; // fallback
    }

    // Form input transaksi
    public function create()
    {
        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

        return view('mobile.transaksi_armada.create', [
            'drawerMenus' => $drawerMenus,
            'armadas'     => collect(),
            'user'        => $user,
        ]);
    }

    public function store(Request $request)
    {
        if (empty($request->armada_id)) {
            // validasi armada baru
            $request->validate([
                'vendor_id' => 'required|exists:vendors,id',
                'nopol'     => 'required|string|max:50|unique:armadas,nopol',
                'panjang'   => 'required|integer',
                'lebar'     => 'required|integer',
                'tinggi'    => 'required|integer',
            ]);

            $armada = Armada::create([
                'vendor_id' => $request->vendor_id,
                'nopol'     => $this->formatNopol($request->nopol),
                'panjang'   => $request->panjang,
                'lebar'     => $request->lebar,
                'tinggi'    => $request->tinggi,
            ]);

            $armadaId = $armada->id;
        } else {
            $request->validate([
                'armada_id' => 'required|exists:armadas,id',
                'panjang'   => 'required|integer',
                'lebar'     => 'required|integer',
                'tinggi'    => 'required|integer',
            ]);
            $armadaId = $request->armada_id;
        }

        $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'plus'       => 'nullable|integer',
        ]);

        $plus     = $request->plus ?? 0;
        $volumeCm = $request->panjang * $request->lebar * ($request->tinggi + $plus);

        \DB::beginTransaction();
        try {
            $lastStruk = TransaksiArmada::whereDate('tgl_transaksi', Carbon::today())
                ->where('project_id', $request->project_id)
                ->lockForUpdate()
                ->max('no_struk');

            $noStruk = ($lastStruk ?? 0) + 1;

            $transaksi = TransaksiArmada::create([
                'armada_id'     => $armadaId,
                'user_id'       => Auth::id(),
                'project_id'    => $request->project_id,
                'tgl_transaksi' => Carbon::now(),
                'panjang'       => $request->panjang,
                'lebar'         => $request->lebar,
                'tinggi'        => $request->tinggi,
                'plus'          => $plus,
                'volume'        => $volumeCm,
                'no_struk'      => $noStruk,
            ]);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan transaksi: '.$e->getMessage()]);
        }

        return redirect()->route('mobile.transaksi_armada.print', $transaksi->id);
    }

    // Tampilkan halaman print / nota
    public function show($id)
    {
        $transaksi = TransaksiArmada::with(['armada','user','project'])->findOrFail($id);

        // hitung ulang volume
        $volumeM3 = round(
            ($transaksi->panjang * $transaksi->lebar * ($transaksi->tinggi + ($transaksi->plus ?? 0))) / 1000000,
            2
        );

        // QR Code base64
        $qrData = 'transaksi:'.$transaksi->id.'|struk:'.$transaksi->no_struk;
        $qrSvg = base64_encode(
            QrCode::size(150)->format('svg')->errorCorrection('H')->generate($qrData)
        );

        return view('mobile.transaksi_armada.print', [
            'transaksi' => $transaksi,
            'project'   => $transaksi->project ?? \App\Models\Project::find($transaksi->project_id),
            'volumeM3'  => $volumeM3,
            'qr'        => $qrSvg,
        ]);
    }

    public function searchArmada(Request $request)
    {
        $queryNormalized = str_replace(' ', '', $request->get('q', ''));

        $armadas = Armada::get()->filter(function($armada) use ($queryNormalized) {
            return stripos(str_replace(' ', '', $armada->nopol), $queryNormalized) !== false;
        })->values();

        return response()->json($armadas);
    }

    public function history(Request $request)
    {
        $projectId = session('project_id');
        if (!$projectId) {
            return redirect()->route('mobile.home')
                ->withErrors(['error' => 'Project belum dipilih.']);
        }

        $tanggal = $request->get('tgl', Carbon::today()->toDateString());

        $transaksis = TransaksiArmada::with(['armada', 'user'])
            ->where('project_id', $projectId)
            ->whereDate('tgl_transaksi', $tanggal)
            ->orderByDesc('tgl_transaksi')
            ->paginate(20);

        $user = Auth::user();
        $drawerMenus = $this->getDrawerMenus($user);

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

        $plus     = $request->plus ?? 0;
        $volumeCm = $request->panjang * $request->lebar * ($request->tinggi + $plus);

        $transaksi->update([
            'panjang' => $request->panjang,
            'lebar'   => $request->lebar,
            'tinggi'  => $request->tinggi,
            'plus'    => $plus,
            'volume'  => $volumeCm,
        ]);

        return redirect()->route('mobile.transaksi_armada.history')
            ->with('success', 'Transaksi berhasil diupdate.');
    }
}
