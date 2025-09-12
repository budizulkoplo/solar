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
       

        return view('dashboard');
    }


}
