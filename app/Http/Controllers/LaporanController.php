<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Penerimaan;
use App\Exports\LaporanPenerimaanExport;
use App\Models\Barang;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function transaksiArmada(Request $request)
    {
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $project = $request->get('project', 'all');

        $projects = DB::table('projects')->select('id','nama_project')->orderBy('nama_project')->get();

        return view('laporan.transaksi_armada', compact('tanggal','project','projects'));
    }

    public function transaksiArmadaData(Request $request)
    {
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $project = $request->get('project', 'all');

        $query = DB::table('transaksi_armada as t')
            ->leftJoin('projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('armadas as a', 'a.id', '=', 't.armada_id')
            ->select(
                't.id',
                't.tgl_transaksi',
                'p.nama_project',
                'a.nopol',
                't.panjang',
                't.lebar',
                't.tinggi',
                't.plus',
                't.volume as volume_m3'
            )
            ->whereDate('t.tgl_transaksi', $tanggal);

        if ($project != 'all') {
            $query->where('t.project_id', $project);
        }

        $data = $query->orderBy('t.tgl_transaksi','asc')->get();

        return response()->json(['data' => $data]);
    }

    public function laporanProject(Request $request)
    {
        // Default start = tgl 1 bulan ini, end = hari ini
        $start = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $end   = $request->end_date ?? Carbon::now()->format('Y-m-d');

        $sub = DB::table('transaksi_armada')
            ->select(
                'project_id',
                'armada_id',
                DB::raw('SUM(volume) as armada_volume'),
                DB::raw('COUNT(id) as transaksi_count')
            )
            ->whereBetween(DB::raw('DATE(tgl_transaksi)'), [$start, $end])
            ->groupBy('project_id', 'armada_id');

        $data = DB::table('projects as p')
            ->joinSub($sub, 't', function($join) {
                $join->on('p.id', '=', 't.project_id');
            })
            ->select(
                'p.id',
                'p.nama_project',
                DB::raw('SUM(t.transaksi_count) as jumlah_input'),
                DB::raw('ROUND(SUM(t.armada_volume), 2) as total_volume'),
                DB::raw('ROUND(SUM(t.armada_volume) / SUM(t.transaksi_count), 2) as rata_volume_armada')
            )
            ->groupBy('p.id', 'p.nama_project')
            ->orderBy('p.nama_project')
            ->get();

        return view('laporan.project', compact('data', 'start', 'end'));
    }

    public function laporanVendor(Request $request)
{
    // Default start = tgl 1 bulan ini, end = hari ini
    $start = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
    $end   = $request->end_date ?? Carbon::now()->format('Y-m-d');

    // Subquery: hitung volume per vendor-armada
    $sub = DB::table('transaksi_armada as t')
        ->join('armadas as a', 'a.id', '=', 't.armada_id')
        ->select(
            'a.vendor_id',
            't.armada_id',
            DB::raw('SUM(t.volume) as armada_volume'),
            DB::raw('COUNT(t.id) as transaksi_count')
        )
        ->whereBetween(DB::raw('DATE(t.tgl_transaksi)'), [$start, $end])
        ->groupBy('a.vendor_id', 't.armada_id');

    // Agregasi per vendor — rata-rata dibagi jumlah transaksi (bukan jumlah armada)
    $data = DB::table('vendors as v')
        ->joinSub($sub, 't', function($join) {
            $join->on('v.id', '=', 't.vendor_id');
        })
        ->select(
            'v.id',
            'v.nama_vendor',
            DB::raw('SUM(t.transaksi_count) as jumlah_input'),                       // total transaksi per vendor
            DB::raw('ROUND(SUM(t.armada_volume), 2) as total_volume'),               // total volume per vendor
            DB::raw('ROUND(SUM(t.armada_volume) / NULLIF(SUM(t.transaksi_count), 0), 2) as rata_volume_armada') // rata per transaksi
        )
        ->groupBy('v.id', 'v.nama_vendor')
        ->orderBy('v.nama_vendor')
        ->get();

    return view('laporan.vendor', compact('data', 'start', 'end'));
}


}
