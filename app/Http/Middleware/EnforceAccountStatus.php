<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * - Signs out deactivated users and users whose institution is deactivated.
 * - Keeps the system owner inside the owner console (it sits above all institutions).
 */
class EnforceAccountStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if ($user->is_system_owner) {
            $allowed = $request->routeIs('owner.*') || $request->routeIs('logout') || $request->is('logout');
            return $allowed ? $next($request) : redirect()->route('owner.dashboard');
        }

        $blocked = null;
        if (!$user->is_active) {
            $blocked = 'Your account has been deactivated. Please contact the system administrator.';
        } elseif (!$this->hasActiveInstitution($user)) {
            $blocked = 'Your institution has been deactivated. Please contact the system administrator.';
        }

        if ($blocked) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $blocked]);
        }

        return $next($request);
    }

    /** Users without an institution yet are let through (their first one is created on demand). */
    private function hasActiveInstitution($user): bool
    {
        if ($user->institution_id) {
            if (!$user->isSuperAdmin()) {
                return (bool) Institution::where('id', $user->institution_id)->value('is_active');
            }
        }

        $institutions = Institution::where('owner_id', $user->ownerId())->pluck('is_active');

        return $institutions->isEmpty() || $institutions->contains(true);
    }
}
