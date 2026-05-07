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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api' => [
                 \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
                 'throttle:api',
                 \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ],
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        $middleware->alias([
            'set.estate' => \App\Http\Middleware\SetEstateManagerFromUrl::class,
        ]);

        $middleware->alias([
            'landlord' => \App\Http\Middleware\EnsureLandlord::class,
        ]);

        $middleware->alias([
            'sanitize' => \App\Http\Middleware\SanitizeInput::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();