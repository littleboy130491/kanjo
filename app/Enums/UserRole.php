<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case EveryAccess = 'every_access';
    case Editor = 'editor';

    /**
     * @return array<int, string>
     */
    public static function panelAccessRoles(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
