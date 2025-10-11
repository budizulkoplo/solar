<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pastikan hanya force https jika benar-benar di balik proxy
        if (request()->isSecure() || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // Pastikan tabel mobilemenu ada agar tidak error di awal migrasi
        if (Schema::hasTable('mobilemenu')) {
            View::composer('mobile.*', function ($view) {
                // Cache hasil query 1 jam
                $drawerMenus = Cache::remember('drawerMenus', 360, function () {
                    return DB::table('mobilemenu')
                        ->where('status', 'drawer')
                        ->orderBy('idmenu')
                        ->get();
                });

                $view->with('drawerMenus', $drawerMenus);
            });
        }
    }
}
