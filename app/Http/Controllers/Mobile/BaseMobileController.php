<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaseMobileController extends Controller
{
    protected $user;
    protected $drawerMenus;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $this->drawerMenus = $this->getDrawerMenus($this->user);

            view()->share([
                'user' => $this->user,
                'drawerMenus' => $this->drawerMenus,
            ]);

            return $next($request);
        });
    }

    protected function getDrawerMenus($user)
    {
        if (!$user) {
            return collect();
        }

        return DB::table('mobilemenu')
            ->where('status', 'drawer')
            ->whereRaw("FIND_IN_SET(?, level)", [$user->ui])
            ->orderBy('idmenu')
            ->get();
    }
}
