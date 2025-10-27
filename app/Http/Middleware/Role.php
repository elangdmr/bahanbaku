<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Role
{
    /**
     * Pakai di route: ->middleware('role:Admin,PPIC')
     * - Admin/Administrator/Superadmin selalu lolos.
     * - Selain itu dicek apakah role user ada di daftar parameter.
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $userRole = strtolower((string) $user->role);
        $allowed  = array_map(fn($r) => strtolower(trim($r)), $roles);

        // Admin aliases selalu boleh masuk
        $adminAliases = ['admin', 'administrator', 'superadmin'];
        if (in_array($userRole, $adminAliases, true)) {
            return $next($request);
        }

        if (!in_array($userRole, $allowed, true)) {
            abort(403, 'Anda tidak berhak mengakses halaman ini.');
        }

        return $next($request);
    }
}
