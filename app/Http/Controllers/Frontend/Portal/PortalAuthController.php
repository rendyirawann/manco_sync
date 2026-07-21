<?php

namespace App\Http\Controllers\Frontend\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Frontend login (separate from /admin/login → /admin/dashboard) used to gate
 * the 18+ area. Reuses LoginRequest (email/WA/username + lockout), and on success
 * returns to the intended page (the 18+ page the user came from), else portal home.
 */
class PortalAuthController extends Controller
{
    public function create()
    {
        return view('frontend.portal.auth.login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();          // handles credential detection + lockout
        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->banned_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'Akun Anda telah dibekukan.']);
        }
        $user->update(['last_ip' => $request->ip(), 'last_login' => now()]);

        // Back to where they came from (e.g. /portal/dewasa), else the portal home.
        return redirect()->intended(route('portal.hub'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('portal.hub');
    }
}
