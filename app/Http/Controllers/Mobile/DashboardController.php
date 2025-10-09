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