<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only users who may open the admin panel may use the MCP server.
 */
class EnsureMcpAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->canAccessPanel(Filament::getPanel('admin'))) {
            return response()->json([
                'message' => 'This account does not have admin panel access, so it cannot use the Kanjo MCP server.',
            ], 403);
        }

        return $next($request);
    }
}
