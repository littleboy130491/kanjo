<?php

use App\Mcp\Servers\KanjoServer;
use App\Mcp\Support\OAuthDiscovery;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery + dynamic client registration for Claude, ChatGPT, and other MCP clients.
Mcp::oauthRoutes();

// RunCloud's nginx denies every dot-path, so a custom nginx rule rewrites /.well-known/oauth-*
// to these routes (see docs/api/mcp.md). They return the same documents as Mcp::oauthRoutes().
Route::get('/oauth-discovery/oauth-authorization-server/{path?}', fn () => response()->json(OAuthDiscovery::authorizationServer()))
    ->where('path', '.*');
Route::get('/oauth-discovery/oauth-protected-resource/{path?}', fn (?string $path = null) => response()->json(OAuthDiscovery::protectedResource($path ?? '')))
    ->where('path', '.*');

Mcp::web('/mcp', KanjoServer::class)
    ->middleware(['auth:api', 'mcp.access', 'throttle:120,1']);
