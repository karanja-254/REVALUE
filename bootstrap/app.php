<?php

use App\Http\Middleware\DenyLogisticsTrading;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust X-Forwarded-Proto/For/Port from proxies on private networks
        // (docker bridge, load balancer) so generated URLs keep https.
        // X-Forwarded-Host is deliberately excluded: tunnels and some proxies
        // pass it through from the client, which would let an attacker poison
        // generated links such as password resets. The real Host header is
        // used instead.
        $middleware->trustProxies(
            at: ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'not-logistics' => DenyLogisticsTrading::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/paystack/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
