<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A database outage is an infrastructure problem, not an app bug:
        // answer 503 for connection-level failures only. Query errors keep
        // their 500 so real bugs stay visible.
        $exceptions->render(function (\Illuminate\Database\QueryException $e) {
            $driverCode = (int) ($e->errorInfo[1] ?? $e->getCode());
            if (in_array($driverCode, [2002, 2003, 2006, 2013], true)) {
                return response()->json(['message' => 'Service temporarily unavailable.'], 503);
            }

            return null;
        });
    })->create();
