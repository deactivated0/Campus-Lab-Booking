<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHasRole
{
    /**
     * Handle an incoming request.
     * Expected usage: middleware('role:Admin|LabStaff')
     */
    public function handle(Request $request, Closure $next, $roles = null)
    {
        $user = $request->user();

        if (! $user) {
            // Let auth middleware handle redirects; deny if unauthenticated here.
            abort(403);
        }

        if (! $roles) {
            return $next($request);
        }

        $roleNames = preg_split('/\|/', $roles);
        // If the User model supports hasAnyRole / hasRole, use it; otherwise, allow only Admins by default.
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($roleNames)) {
                return $next($request);
            }
        } else {
            // Fallback: check a simple 'role' attribute if present
            $userRole = $user->role ?? null;
            if ($userRole && in_array($userRole, $roleNames, true)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
