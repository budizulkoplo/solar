<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Barang;
use App\Models\StokUnit;
use App\Models\StockOpnameHDR;
use App\Models\StockOpnameDTL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class MobileStokOpnameController extends BaseMobileController
{
    // Halaman scan barcode
    public function index()
    {
        return view('mobile.stokopname.index');
    }

    // Redirect ke form opname berdasarkan hasil scan
    public function scanResult(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        $barang = Barang::where('kode_barang', $request->barcode)->first();

        if (!$barang) {
            return redirect()->route('mobile.stokopname.index')
                ->with('error', 'Produk tidak ditemukan!');
        }

        return redirect()->route('mobile.stokopname.create', ['id' => $barang->id]);
    }

    // Form input opname barang terpilih
    public function create($id)
    {
        $barang = Barang::findOrFail($id);
        return view('mobile.stokopname.create', compact('barang'));
    }

    // Simpan opname
    public function store(Request $request)
    {
        $request->validate([
            'tgl_opname' => 'required|date',
            'id'         => 'required|array',
            'code'       => 'required|array',
            'qty'        => 'required|array',
            'exp'        => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $date   = Carbon::parse($request->tgl_opname);
            $unitId = Auth::user()->unit_kerja;
            $userId = Auth::user()->id;

            $dataGrouped = [];
            foreach ($request->id as $index => $idBarang) {
                $qty = $request->qty[$index] ?? null;
                $exp = $request->exp[$index] ?? null;

                if (!$qty || !$exp) {
                    continue; // skip baris kosong
                }

                $dataGrouped[$idBarang]['code'] = $request->code[$index];
                $dataGrouped[$idBarang]['items'][] = [
                    'qty' => (int) $qty,
                    'exp' => $exp,
                ];
            }

            foreach ($dataGrouped as $idBarang => $group) {
                $totalQty = array_sum(array_column($group['items'], 'qty'));

                $stoksys = StokUnit::firstOrCreate(
                    ['barang_id' => $idBarang, 'unit_id' => $unitId],
                    ['stok' => 0]
                );

                $hdr = StockOpnameHDR::firstOrNew([
                    'id_unit'   => $unitId,
                    'id_barang' => $idBarang,
                    'tgl_opname'=> $date->format('Y-m-d'),
                ]);

                $hdr->kode_barang = $group['code'];
                $hdr->user        = $userId;
                $hdr->stock_sistem= $stoksys->stok;
                $hdr->stock_fisik = $totalQty;
                $hdr->status      = "sukses";
                $hdr->save();

                // replace detail lama
                StockOpnameDTL::where('opnameid', $hdr->id)->delete();

                foreach ($group['items'] as $item) {
                    StockOpnameDTL::create([
                        'opnameid'    => $hdr->id,
                        'id_barang'   => $idBarang,
                        'qty'         => $item['qty'],
                        'expired_date'=> $item['exp'],
                    ]);
                }

                // update stok sistem
                $stoksys->stok = $totalQty;
                $stoksys->save();
            }

            DB::commit();
            return response()->json([
                'success'  => true,
                'redirect' => route('mobile.stokopname.index'),
                'message'  => 'Stock opname berhasil disimpan.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Gagal menyimpan stok opname',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
