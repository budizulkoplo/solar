<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use App\Models\Setting;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function create(): View|RedirectResponse
    {
        // Jika user sudah login, redirect sesuai role
        if (Auth::check()) {
            return redirect()->to($this->redirectTo());
        }
        $setting = Setting::first();

        return view('auth.login', compact('setting'));
    }

    /**
     * Proses login
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Redirect sesuai role
        return redirect()->to($this->redirectTo());
    }

    /**
     * Logout user
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect setelah login berdasarkan role
     */
    protected function redirectTo(): string
    {
        $user = Auth::user();

        return match ($user->ui) {
            'admin' => route('dashboard'),
            'user'  => route('mobile.home'),
            default => route('login'), // fallback
        };
    }

}
