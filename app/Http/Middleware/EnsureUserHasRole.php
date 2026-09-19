<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more roles, e.g. middleware('role:admin,super_admin').
 *
 * Super admins always pass, since they have full platform control.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->isSuperAdmin() && ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
