<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gate for 18+ content: requires a logged-in Superadmin.
 * - Guest        → redirect to the FRONTEND login (/masuk), remembering the
 *   intended 18+ URL so login returns there (NOT /admin/login → /admin/dashboard).
 * - Logged in but not Superadmin → 403.
 */
class EnsureSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->guest(route('portal.login'));
        }
        if (!Auth::user()->hasRole(['Superadmin', 'superadmin'])) {
            abort(403, 'Halaman 18+ khusus Superadmin.');
        }
        return $next($request);
    }
}
