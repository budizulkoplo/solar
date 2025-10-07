<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        // 🔹 Abaikan route pilih project/module
        $excludedRoutes = ['choose.project', 'choose.project.store'];
        $currentRoute = strtolower($request->route()->getName() ?? '');
        if (in_array($currentRoute, $excludedRoutes)) {
            return $next($request);
        }

        $userRole = $user->getRoleNames()->first();
        $activeModule = session('active_project_module');

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
                  ->orWhereNull('module'); // menu umum tetap tampil
            });
        }

        $menus = $menuQuery->get();
        $request->merge(['menu' => $this->buildTree($menus)]);

        // 🔹 Batasi akses lintas module, kecuali menu umum
        if ($activeModule) {
            $allowedLinks = $menus->pluck('link')->filter()->map(fn($link) => strtolower($link))->toArray();
            if ($currentRoute && !in_array($currentRoute, $allowedLinks)) {
                abort(403, 'Anda tidak memiliki akses ke module ini.');
            }
        }

        return $next($request);
    }
}
