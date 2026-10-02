<?php

use App\Exceptions\DocumentApiConfigurationException;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Http\Middleware\DocumentApiKeyMiddleware;
use App\Http\Middleware\DocumentAuthThrottleMiddleware;
use App\Http\Middleware\EnsureMcpAccess;
use App\Http\Middleware\NoIndexMiddleware;
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
        $middleware->append(NoIndexMiddleware::class);

        // OAuth consent (MCP connectors) sends guests to the Filament login, which returns them here afterwards.
        $middleware->redirectGuestsTo(fn (): string => route('filament.admin.auth.login'));

        $middleware->alias([
            'document.access' => DocumentAccessMiddleware::class,
            'document.auth.throttle' => DocumentAuthThrottleMiddleware::class,
            'document.api' => DocumentApiKeyMiddleware::class,
            'mcp.access' => EnsureMcpAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            // MCP clients need a JSON 401 (not a login redirect) to start OAuth discovery.
            return $request->is('api/*', 'mcp');
        });

        $exceptions->render(function (DocumentApiConfigurationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        });
    })->create();
