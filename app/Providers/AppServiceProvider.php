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
        // Force HTTPS jika berada di balik proxy
        if (request()->isSecure() || request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }

        // Pastikan tabel mobilemenu ada
        if (Schema::hasTable('mobilemenu')) {
            View::composer('mobile.*', function ($view) {
                $userId = auth()->check() ? auth()->user()->id : null;

                // Ambil menu drawer sesuai user
                $drawerMenus = DB::table('mobilemenu')
                    ->where('status', 'drawer')
                    ->orderBy('idmenu')
                    ->when($userId, function($query) use ($userId) {
                        $query->where(function($q) use ($userId) {
                            $q->whereNull('userakses') // menu untuk semua user
                            ->orWhere('userakses', 'like', "%{$userId}%"); // menu khusus user
                        });
                    })
                    ->get();

                $view->with('drawerMenus', $drawerMenus);
            });
        }
    }
}
