<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('document_api.key');

        if (! is_string($configuredKey) || $configuredKey === '') {
            return response()->json([
                'message' => 'Document API is not configured.',
            ], 401);
        }

        $provided = $request->bearerToken() ?: $request->header('X-Api-Key');

        if (! is_string($provided) || $provided === '' || ! hash_equals($configuredKey, $provided)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
