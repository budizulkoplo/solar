<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobileController extends Controller
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

        // Ambil menu dari tabel mobilemenu
        $drawerMenus = $this->getDrawerMenus($user);

        return view('mobile.index', compact('user', 'drawerMenus'));
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