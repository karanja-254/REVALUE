<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logistics accounts move items, they do not trade them. Blocks selling,
 * donating, recycling and claiming donations even if the URL is typed by hand.
 */
class DenyLogisticsTrading
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isLogistics()) {
            abort(403, 'Logistics accounts handle pickups and deliveries, not listings.');
        }

        return $next($request);
    }
}
