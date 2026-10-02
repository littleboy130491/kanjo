<?php

use App\Mcp\Servers\KanjoServer;
use Laravel\Mcp\Facades\Mcp;

// OAuth discovery + dynamic client registration for Claude, ChatGPT, and other MCP clients.
Mcp::oauthRoutes();

Mcp::web('/mcp', KanjoServer::class)
    ->middleware(['auth:api', 'mcp.access', 'throttle:120,1']);
