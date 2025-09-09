<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Penjualan;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $jumlahVendors = \App\Models\Vendor::count();
        $jumlahArmadas = \App\Models\Armada::count();
        $jumlahProjects = \App\Models\Project::count();

        $projectsSummary = \App\Models\Project::withCount('transaksiArmada')
            ->withSum('transaksiArmada', 'volume')
            ->get()
            ->map(function($p){
                return (object)[
                    'nama_project' => $p->nama_project,
                    'jumlah_input' => $p->transaksi_armada_count,
                    'total_volume' => $p->transaksi_armada_sum_volume
                ];
            });

        return view('dashboard', compact(
            'jumlahVendors', 'jumlahArmadas', 'jumlahProjects', 'projectsSummary'
        ));
    }


}
