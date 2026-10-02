<?php

namespace App\Services\DocumentApi;

use App\Exceptions\DocumentApiConfigurationException;
use App\Models\User;
use Closure;

class DocumentApiAuthor
{
    private static ?User $actingAs = null;

    /**
     * Attribute documents written inside the callback to the given user instead of
     * DOCUMENT_API_USER_ID. Used by the MCP server, where the caller is an OAuth user.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public static function actingAs(User $user, Closure $callback): mixed
    {
        $previous = self::$actingAs;
        self::$actingAs = $user;

        try {
            return $callback();
        } finally {
            self::$actingAs = $previous;
        }
    }

    public static function user(): User
    {
        if (self::$actingAs instanceof User) {
            return self::$actingAs;
        }

        $userId = config('document_api.user_id');

        if (! is_int($userId) || $userId < 1) {
            throw new DocumentApiConfigurationException(
                'DOCUMENT_API_USER_ID is not configured.',
            );
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            throw new DocumentApiConfigurationException(
                'DOCUMENT_API_USER_ID does not match an existing user.',
            );
        }

        return $user;
    }
}
