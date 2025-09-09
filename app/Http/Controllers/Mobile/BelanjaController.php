<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\KonfigBunga;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Penjualan;
use App\Models\PenjualanCicil;
use App\Models\PenjualanDetail;
use App\Models\StokUnit;
use App\Models\User;

class BelanjaController extends BaseMobileController

{
    public function index()
    {
        $user = Auth::user();

        if ($user->ui !== 'user') {
            return redirect()->route('dashboard')->with('warning', 'Anda tidak memiliki akses ke halaman ini');
        }

        $tokoList = DB::table('unit')
            ->where('jenis', 'toko')
            ->whereNull('deleted_at')
            ->orderBy('nama_unit')
            ->get();

        return view('mobile.belanja.toko', compact('user', 'tokoList'));
    }

    public function produk(Request $request, $unitId)
    {
        $user = Auth::user();

        if ($user->ui !== 'user') {
            return redirect()->route('dashboard')->with('warning', 'Anda tidak memiliki akses ke halaman ini');
        }

        // Simpan unit_id di session supaya bisa dipakai saat checkout
        session(['cart_unit_id' => $unitId]);

        $query = DB::table('barang as b')
            ->join('stok_unit as su', 'su.barang_id', '=', 'b.id')
            ->where('su.unit_id', $unitId)
            ->whereNull('b.deleted_at')
            ->whereNull('su.deleted_at')
            ->select('b.id','b.kode_barang','b.nama_barang','b.kategori','b.satuan','b.harga_jual','su.stok','b.img');

        // Filter pencarian jika ada parameter q
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('b.nama_barang', 'like', "%$q%")
                    ->orWhere('b.kode_barang', 'like', "%$q%");
            });
        }

        $produkList = $query->orderBy('b.nama_barang')->get();

        $toko = DB::table('unit')->where('id', $unitId)->first();

        $cart = session()->get('cart', []);
        $cartCount = collect($cart)->sum('qty');

        return view('mobile.belanja.produk', compact('user', 'toko', 'produkList', 'cartCount'));
    }

    public function addToCart(Request $request)
    {
        $cart = session()->get('cart', []);

        $id = $request->id;
        $nama = $request->nama;
        $harga = $request->harga;
        $qty = $request->qty ?? 1;

        if (isset($cart[$id])) {
            $cart[$id]['qty'] += $qty;
        } else {
            $cart[$id] = [
                'id'    => $id,
                'nama'  => $nama,
                'harga' => $harga,
                'qty'   => $qty,
            ];
        }

        session()->put('cart', $cart);

        return redirect()->back()->with('success', 'Produk ditambahkan ke keranjang');
    }

    /**
     * Tampilkan keranjang
     */
    public function cart()
    {
        $cart = session()->get('cart', []);
        return view('mobile.belanja.cart', compact('cart'));
    }

    /**
     * Update qty produk
     */
    public function updateCart(Request $request)
    {
        $cart = session()->get('cart', []);
        $id = $request->id;

        if (isset($cart[$id])) {
            $cart[$id]['qty'] = $request->qty;
            session()->put('cart', $cart);
        }

        return redirect()->back();
    }

    /**
     * Hapus produk dari keranjang
     */
    public function removeFromCart(Request $request)
    {
        $cart = session()->get('cart', []);
        $id = $request->id;

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'Produk dihapus dari keranjang');
    }

    public function checkout()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('mobile.belanja.cart')->with('warning', 'Keranjang kosong');
        }
        return view('mobile.belanja.checkout', compact('cart'));
    }
    function genCode(){
        $total = Penjualan::withTrashed()->whereDate('created_at', date("Y-m-d"))->count();
        $nomorUrut = $total + 1;
        $newcode='INV-'.date("ymd").str_pad($nomorUrut, 3, '0', STR_PAD_LEFT);
        return $newcode;
    }
    public function processCheckout(Request $request)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('mobile.belanja.cart')->with('warning', 'Keranjang kosong');
        }

        $unitId = session('cart_unit_id', Auth::user()->unit_kerja);

        DB::beginTransaction();
        try {
            $subtotal = collect($cart)->sum(fn($i) => $i['harga'] * $i['qty']);
            $grandtotal = $subtotal;

            $penjualan = new Penjualan;
            $penjualan->nomor_invoice = $this->genCode();
            $penjualan->tanggal = Carbon::now();
            $penjualan->subtotal = $subtotal;
            $penjualan->grandtotal = $grandtotal;
            $penjualan->total = $grandtotal;
            $penjualan->anggota_id = $request->anggota_id ?? '';
            $penjualan->customer = $request->customer ?? 'Umum';
            $penjualan->diskon = 0;
            $penjualan->unit_id = $unitId;
            $penjualan->note = $request->note;
            $penjualan->created_user = Auth::user()->id;
            $penjualan->type_order = 'mobile';
            $penjualan->status_ambil = 'pesan';
            $penjualan->metode_bayar = $request->metode_bayar;
            if ($request->metode_bayar == 'cicilan') {
                $tenor=$request->jmlcicilan ?? 1;
                $bunga = KonfigBunga::select('bunga_barang')->first();
                $penjualan->status = 'hutang';
                $penjualan->tenor = $tenor;
                $result = DB::select("SELECT hitung_cicilan(?, ?, ?, ?) AS jumlah", [$grandtotal, $bunga->bunga_barang, $tenor, 1]);
                $cicilanpertama = $result[0]->jumlah;

                $totalcicilan = PenjualanCicil::where(['anggota_id'=>Auth::user()->id,'status'=>'hutang'])->sum('total_cicilan');

                $batas = 0.35 * Auth::user()->gaji; // 35% dari gaji
                if (($totalcicilan+$cicilanpertama) > $batas) { //PR  hitung hutang yg masih aktif jika < $user->limit_hutang maka lolos
                    return response()->json('Tidak dapat diproses, Melebihi batas limit',500);
                }

                $penjualan->bunga_barang = $bunga->bunga_barang;
                $penjualan->kembali = 0;
                $penjualan->dibayar = 0;
            } else {
                $penjualan->status = 'pending';
                $penjualan->tenor = 0;
                $penjualan->kembali = 0;
                $penjualan->dibayar = 0;
            }

            $penjualan->save();

            foreach ($cart as $item) {
                $detail = new PenjualanDetail;
                $detail->penjualan_id = $penjualan->id;
                $detail->barang_id = $item['id'];
                $detail->qty = $item['qty'];
                $detail->harga = $item['harga'];
                $detail->save();
            }

            if($request->metodebayar == 'cicilan'){
                $tenor=$request->jmlcicilan ?? 1;
                for ($i = 1; $i <= $request->jmlcicilan; $i++) {
                    $pokoktotal = DB::select("SELECT hitung_pokok(?, ?) AS jumlah", [$grandtotal, $tenor]);
                    $bungatotal = DB::select("SELECT hitung_bunga(?, ?, ?, ?) AS jumlah", [$grandtotal, $bunga->bunga_barang, $tenor, $i]);
                    $cicilan = new PenjualanCicil();
                    $cicilan->penjualan_id = $penjualan->id;
                    $cicilan->cicilan = $i;
                    $cicilan->anggota_id = Auth::user()->id;
                    $cicilan->pokok = $pokoktotal[0]->jumlah;
                    $cicilan->bunga = $bungatotal[0]->jumlah;
                    $cicilan->total_cicilan = $pokoktotal[0]->jumlah+$bungatotal[0]->jumlah;
                    $cicilan->status = 'hutang';
                    $cicilan->save();
                }
            }

            DB::commit();
            session()->forget('cart');
            session()->forget('cart_unit_id');

            return redirect()->route('mobile.belanja.history.detail', ['id' => $penjualan->id])
                 ->with('success', 'Pesanan berhasil disimpan, Invoice: '.$penjualan->nomor_invoice);


        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal simpan order: '.$e->getMessage());
        }
    }

    public function history()
    {
        $user = Auth::user();

        // Ambil semua penjualan mobile user ini
        $riwayat = Penjualan::where('created_user', $user->id)
                    ->where('type_order', 'mobile')
                    ->orderBy('tanggal', 'desc')
                    ->get();

        return view('mobile.belanja.history', compact('riwayat'));
    }

    public function historyDetail($id)
    {
        $user = Auth::user();

        $penjualan = Penjualan::where('id', $id)
                    ->where('created_user', $user->id)
                    ->where('type_order', 'mobile')
                    ->firstOrFail();

        $detail = PenjualanDetail::where('penjualan_id', $penjualan->id)
                    ->get();

        return view('mobile.belanja.history_detail', compact('penjualan', 'detail'));
    }

    public function cancelOrder($id)
    {
        $penjualan = Penjualan::findOrFail($id);

        // Hanya bisa batal jika status masih pending
        if($penjualan->status === 'pending') {
            $penjualan->status = 'batal';
            $penjualan->deleted_at = Carbon::now(); // menandai sebagai batal
            $penjualan->save();

            return redirect()->route('mobile.belanja.history')
                            ->with('success', 'Pesanan berhasil dibatalkan');
        }

        return redirect()->back()->with('error', 'Pesanan tidak bisa dibatalkan');
    }

}
