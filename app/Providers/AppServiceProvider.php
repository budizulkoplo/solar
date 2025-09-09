<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (
            request()->header('x-forwarded-proto') === 'https'
        ) {
            \URL::forceScheme('https');
        }
    }

}
