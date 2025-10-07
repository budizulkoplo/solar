<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalApp
{
    function buildTree($elements, $parentId = null)
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

        // 🔹 Ambil module aktif dari session
        $activeModule = session('active_project_module');

        // 🔹 Filter menu hanya berdasarkan module yang sedang aktif
        $query = Menu::orderBy('seq', 'asc');
        if ($activeModule) {
            $query->where('module', $activeModule);
        }

        $menu = $query->get();

        // 🔹 Inject menu ke request agar bisa diakses di seluruh view/controller
        $request->merge([
            'menu' => $this->buildTree($menu),
        ]);

        return $next($request);
    }
}
