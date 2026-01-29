<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class GlobalApp
{
    private function buildTree($elements, $parentId = null)
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    public function handle(Request $request, Closure $next, $role = null): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $activeModule = session('active_project_module');
        $userRole = $user->getRoleNames()->first();

        // Ambil menu sesuai role TANPA filter module yang ketat
        $menuQuery = Menu::orderBy('seq', 'asc');
        
        if ($userRole) {
            $menuQuery->where(function ($q) use ($userRole) {
                $q->where('role', 'like', "%;$userRole;%")
                  ->orWhere('role', 'like', "$userRole;%")
                  ->orWhere('role', 'like', "%;$userRole")
                  ->orWhere('role', '=', $userRole)
                  ->orWhereNull('role');
            });
        }

        // Hanya filter module jika ada activeModule DAN bukan untuk menu utama
        if ($activeModule) {
            $menuQuery->where(function ($q) use ($activeModule) {
                // Izinkan menu dengan module yang sesuai ATAU null ATAU module kosong
                $q->where('module', $activeModule)
                  ->orWhereNull('module')
                  ->orWhere('module', '')
                  // Izinkan juga menu utama
                  ->orWhereIn('name', ['Dashboard', 'Master', 'Settings', 'HRIS', 'Project', 'PT', 'Marketing', 'Laporan']);
            });
        }

        $menus = $menuQuery->get();

        // Bangun tree menu
        $request->merge(['menu' => $this->buildTree($menus)]);

        // Routes yang selalu diizinkan
        $alwaysAllowed = [
            'choose.project',
            'choose.project.store',
            'login',
            'logout',
            'dashboard',
            'companies.edit',
            'hris.payroll.slip',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'profile.upload',
            'dashboard.pesananHariIniData',
        ];

        $currentRoute = strtolower($request->route()->getName() ?? '');

        if (in_array($currentRoute, $alwaysAllowed)) {
            return $next($request);
        }

        // Izinkan route mobile dan auth
        if (Str::startsWith($currentRoute, 'mobile.') || Str::startsWith($currentRoute, 'auth.')) {
            return $next($request);
        }

        if (!$activeModule) {
            return $next($request);
        }

        // Ambil semua route yang diizinkan dari menu (parameter kedua dihapus)
        $allowedRoutes = $this->getAllAllowedRoutes($menus);

        // AUTO ALLOW: jika route child dari parent menu, izinkan
        $segments = $request->segments();
        if (count($segments) > 0) {
            $firstSegment = strtolower($segments[0]);
            foreach ($menus as $menu) {
                if ($menu->link && strtolower($menu->link) == $firstSegment) {
                    return $next($request); // izinkan semua child route
                }
            }
        }

        // Izinkan khusus routes HRIS
        if (Str::startsWith($currentRoute, 'hris.') || 
            Str::startsWith($currentRoute, 'master.') && Str::contains($currentRoute, ['unitkerja', 'kelompokjam', 'jadwal', 'gaji']) ||
            $currentRoute === 'pegawai.list') {
            // Cek apakah role punya akses ke module HRIS
            $hasHrisAccess = Menu::where('module', 'hris')
                ->where(function ($q) use ($userRole) {
                    $q->where('role', 'like', "%;$userRole;%")
                      ->orWhere('role', 'like', "$userRole;%")
                      ->orWhere('role', 'like', "%;$userRole")
                      ->orWhere('role', '=', $userRole);
                })
                ->exists();
            
            if ($hasHrisAccess) {
                return $next($request);
            }
        }

        // Cek akses berdasarkan nama route
        $hasAccess = in_array($currentRoute, $allowedRoutes) ||
                     $this->isRouteAllowed($currentRoute, $allowedRoutes);

        if (!$hasAccess) {
            \Log::info('Access denied', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'current_route' => $currentRoute,
                'allowed_routes' => $allowedRoutes,
                'active_module' => $activeModule,
                'menus' => $menus->pluck('name', 'link')->toArray()
            ]);
            abort(403, 'Anda tidak memiliki akses ke module ini.');
        }

        return $next($request);
    }

    private function getAllAllowedRoutes($menus)
    {
        $allowedRoutes = [];

        foreach ($menus as $menu) {
            if ($menu->link) {
                $baseRoute = strtolower($menu->link);
                $allowedRoutes[] = $baseRoute;

                // Tambahkan resource conventional
                $allowedRoutes = array_merge($allowedRoutes, [
                    $baseRoute . '.index',
                    $baseRoute . '.show',
                    $baseRoute . '.create',
                    $baseRoute . '.store',
                    $baseRoute . '.edit',
                    $baseRoute . '.update',
                    $baseRoute . '.destroy',
                ]);
            }
        }

        return array_unique($allowedRoutes);
    }

    private function isRouteAllowed($currentRoute, $allowedRoutes)
    {
        foreach ($allowedRoutes as $allowedRoute) {
            if ($currentRoute === $allowedRoute || Str::startsWith($currentRoute, $allowedRoute . '.')) {
                return true;
            }

            $currentParts = explode('.', $currentRoute);
            $allowedParts = explode('.', $allowedRoute);
            if (count($currentParts) > 0 && count($allowedParts) > 0 && $currentParts[0] === $allowedParts[0]) {
                return true;
            }

            if ($this->matchesRoutePattern($currentRoute, $allowedRoute)) {
                return true;
            }
        }

        return false;
    }

    private function matchesRoutePattern($currentRoute, $allowedRoute)
    {
        if (!Str::contains($allowedRoute, '{')) {
            return false;
        }

        $pattern = preg_quote($allowedRoute, '/');
        $pattern = str_replace('\\{', '{', $pattern);
        $pattern = preg_replace('/\{[^}]+\}/', '[^/.]+', $pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $currentRoute) === 1;
    }
}