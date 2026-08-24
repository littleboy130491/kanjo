<?php

namespace App\Services\DocumentApi;

use App\Exceptions\DocumentApiConfigurationException;
use App\Models\User;

class DocumentApiAuthor
{
    public static function user(): User
    {
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
