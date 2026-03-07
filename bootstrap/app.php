<?php

use App\Http\Middleware\DocumentAuthThrottleMiddleware;
use App\Http\Middleware\NoIndexMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\DocumentAccessMiddleware;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(NoIndexMiddleware::class);

        $middleware->alias([
            'document.access' => DocumentAccessMiddleware::class,
            'document.auth.throttle' => DocumentAuthThrottleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
