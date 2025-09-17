<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckActiveProject
{
    public function handle(Request $request, Closure $next)
    {
        // Boleh akses choose-project & logout tanpa project aktif
        if (!session()->has('active_project_id')
            && !$request->is('choose-project')
            && !$request->is('choose-project/*')
            && !$request->is('logout')) 
        {
            return redirect()->route('choose.project');
        }

        return $next($request);
    }
}
