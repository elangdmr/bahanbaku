<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login.
     * Jika sudah login, langsung arahkan ke halaman sesuai role.
     */
    public function login()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return redirect()->route($this->redirectRouteNameByRole($user));
        }

        return view('auth.login');
    }

    /**
     * Proses login.
     */
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['nullable', 'boolean'],
        ], [], [
            'email'    => 'Email',
            'password' => 'Password',
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        // Hanya ambil email & password untuk attempt
        $attemptCreds = [
            'email'    => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($attemptCreds, $remember)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            return redirect()->intended(route($this->redirectRouteNameByRole($user)));
        }

        // Kredensial salah
        throw ValidationException::withMessages([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    /**
     * (Opsional) Logout user.
     * Jika kamu sudah punya logout di controller lain, method ini tidak perlu diroute.
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Tentukan route name tujuan setelah login, berdasarkan role user.
     */
    protected function redirectRouteNameByRole(User $user): string
    {
        return match ($user->role) {
            'Admin'      => 'dashboard',             // admin dashboard
            'PPIC'       => 'halal.index',           // modul Halal PPIC
            'Purchasing' => 'purch-vendor.index',    // modul Purchasing Vendor
            'R&D'        => 'trial-rnd.index',       // modul Trial R&D
            default      => 'home',                  // fallback
        };
    }
}
