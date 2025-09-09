<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman utama mobile
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function index()
{
    $user = Auth::user();

    // Redirect jika bukan user
    if ($user->ui !== 'user') {
        return redirect()->route('dashboard')->with('warning', 'Anda tidak memiliki akses ke halaman ini');
    }

    // Cek project di session
    if (!session()->has('project_id')) {
        return redirect()->route('mobile.project.select')->with('warning', 'Pilih project terlebih dahulu');

    }

    $drawerMenus = $this->getDrawerMenus($user);

    $projectId = session('project_id');
    $namaProject = session('nama_project');

    return view('mobile.index', compact('user', 'drawerMenus', 'projectId', 'namaProject'));
}


    /**
     * Mendapatkan drawer menus untuk user tertentu
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    protected function getDrawerMenus($user)
    {
        return DB::table('mobilemenu')
            ->where('status', 'drawer')
            ->whereRaw("FIND_IN_SET(?, level)", [$user->ui])
            ->orderBy('idmenu')
            ->get();
    }
}