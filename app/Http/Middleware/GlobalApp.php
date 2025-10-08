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

        if ($role && $user->ui !== $role) {
            return match ($user->ui) {
                'admin' => redirect()->route('dashboard'),
                'user' => redirect()->route('mobile.home'),
                default => redirect()->route('login'),
            };
        }

        $activeModule = session('active_project_module');
        $userRole = $user->getRoleNames()->first();

        // Ambil menu sesuai role & module
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

        if ($activeModule) {
            $menuQuery->where(function ($q) use ($activeModule) {
                $q->where('module', $activeModule)
                  ->orWhereNull('module');
            });
        }

        $menus = $menuQuery->get();

        // Bangun tree menu
        $request->merge([
            'menu' => $this->buildTree($menus),
        ]);

        // Routes yang selalu diizinkan (pilih project/module)
        $alwaysAllowed = [
            'choose.project',
            'choose.project.store',
            'login',
            'logout',
            'dashboard',
            'dashboard.pesananHariIniData',
        ];

        $currentRoute = strtolower($request->route()->getName() ?? '');

        if (in_array($currentRoute, $alwaysAllowed)) {
            return $next($request);
        }

        // PERBAIKAN: Izinkan route mobile tanpa module check
        if (Str::startsWith($currentRoute, 'mobile.')) {
            return $next($request);
        }

        // PERBAIKAN: Izinkan route auth
        if (Str::startsWith($currentRoute, 'auth.')) {
            return $next($request);
        }

        // Jika tidak ada active module, izinkan akses
        if (!$activeModule) {
            return $next($request);
        }

        // Ambil semua route yang diizinkan dari menu
        $allowedRoutes = $this->getAllAllowedRoutes($menus);

        $hasAccess = in_array($currentRoute, $allowedRoutes) || 
                    $this->isRouteAllowed($currentRoute, $allowedRoutes);

        if (!$hasAccess) {
            // Debug information - bisa dihapus setelah testing
            \Log::info('Access denied', [
                'current_route' => $currentRoute,
                'allowed_routes' => $allowedRoutes,
                'active_module' => $activeModule
            ]);
            abort(403, 'Anda tidak memiliki akses ke module ini.');
        }

        return $next($request);
    }

    /**
     * Mendapatkan semua route yang diizinkan dari menu
     */
    private function getAllAllowedRoutes($menus)
    {
        $allowedRoutes = [];
        
        foreach ($menus as $menu) {
            if ($menu->link) {
                $baseRoute = strtolower($menu->link);
                $allowedRoutes[] = $baseRoute;
                
                // Untuk route resource conventional (index, show, edit, update, destroy)
                $allowedRoutes = array_merge($allowedRoutes, [
                    $baseRoute . '.index',
                    $baseRoute . '.show',
                    $baseRoute . '.create',
                    $baseRoute . '.store',
                    $baseRoute . '.edit',
                    $baseRoute . '.update',
                    $baseRoute . '.destroy',
                ]);

                // PERBAIKAN: Untuk route dengan pattern seperti pegawai.getdata, users.getdata, dll
                // Ambil base segment pertama sebagai parent
                $routeParts = explode('.', $baseRoute);
                $parentRoute = $routeParts[0];
                
                // Tambahkan semua kemungkinan route child berdasarkan parent
                $commonChildRoutes = [
                    'getdata', 'getcode', 'list', 'store', 'update', 'destroy', 
                    'show', 'edit', 'create', 'assignrole', 'updatepassword',
                    'kasihrole', 'permission', 'addrole', 'deleterole', 'deletepermission',
                    'toggle', 'getuserprojects', 'getsaldo', 'updatejenis',
                    'datamenu', 'transaksi_armada', 'transaksi_armada_data',
                    'laporan_project', 'laporan_vendor'
                ];

                foreach ($commonChildRoutes as $child) {
                    $allowedRoutes[] = $parentRoute . '.' . $child;
                }

                // Untuk route dengan prefix (seperti companies.projects)
                if (count($routeParts) > 1) {
                    $parentPrefix = $routeParts[0];
                    foreach ($commonChildRoutes as $child) {
                        $allowedRoutes[] = $parentPrefix . '.' . $child;
                    }
                }
            }
        }
        
        return array_unique($allowedRoutes);
    }

    /**
     * Cek apakah route saat ini diizinkan berdasarkan pattern matching
     */
    private function isRouteAllowed($currentRoute, $allowedRoutes)
    {
        foreach ($allowedRoutes as $allowedRoute) {
            // Exact match
            if ($currentRoute === $allowedRoute) {
                return true;
            }

            // Prefix match (misal: pegawai. diawali dengan pegawai.)
            if (Str::startsWith($currentRoute, $allowedRoute . '.')) {
                return true;
            }

            // PERBAIKAN: Group prefix match
            // Jika allowedRoute adalah 'pegawai', maka izinkan semua 'pegawai.*'
            $currentParts = explode('.', $currentRoute);
            $allowedParts = explode('.', $allowedRoute);
            
            if (count($currentParts) > 0 && count($allowedParts) > 0) {
                if ($currentParts[0] === $allowedParts[0]) {
                    return true;
                }
            }

            // Pattern match untuk route dengan parameter (misal: companies/{id})
            if ($this->matchesRoutePattern($currentRoute, $allowedRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek pattern matching untuk route dengan parameter
     */
    private function matchesRoutePattern($currentRoute, $allowedRoute)
    {
        // Jika allowed route tidak mengandung parameter, skip
        if (!Str::contains($allowedRoute, '{')) {
            return false;
        }

        // Ubah pattern route menjadi regex
        $pattern = preg_quote($allowedRoute, '/');
        $pattern = str_replace('\\{', '{', $pattern);
        $pattern = preg_replace('/\{[^}]+\}/', '[^/.]+', $pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $currentRoute) === 1;
    }
}