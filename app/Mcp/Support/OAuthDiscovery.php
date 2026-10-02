<?php

namespace App\Mcp\Support;

use Laravel\Mcp\Server\Registrar;

/**
 * Exposes laravel/mcp's OAuth discovery documents so they can also be served outside /.well-known.
 */
class OAuthDiscovery extends Registrar
{
    /**
     * @return array<string, mixed>
     */
    public static function authorizationServer(): array
    {
        return static::authorizationServerMetadata('oauth');
    }

    /**
     * @return array<string, mixed>
     */
    public static function protectedResource(string $path): array
    {
        return static::protectedResourceMetadata($path);
    }
}
