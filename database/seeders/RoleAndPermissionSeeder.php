<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const RESTRICTED_PERMISSION_SUBJECTS = [
        'Activity',
        'Company',
        'ProposalContentDefault',
        'Role',
        'ResourceLock',
        'SpkContentDefault',
    ];

    /**
     * @var array<int, string>
     */
    private const EDITOR_ALLOWED_USER_ACTIONS = [
        'View',
        'Update',
    ];

    /**
     * @var array<int, string>
     */
    private const EDITOR_RESTRICTED_DESTRUCTIVE_SUBJECTS = [
        'Client',
        'Service',
    ];

    /**
     * @var array<int, string>
     */
    private const EDITOR_RESTRICTED_DESTRUCTIVE_ACTIONS = [
        'Delete',
        'DeleteAny',
        'ForceDelete',
        'ForceDeleteAny',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(FilamentShield::getResources())
            ->pluck('permissions')
            ->flatten(1)
            ->pluck('key')
            ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));

        $superAdminRole = Role::findOrCreate(UserRole::SuperAdmin->value, 'web');
        $editorRole = Role::findOrCreate(UserRole::Editor->value, 'web');

        $superAdminRole->syncPermissions($permissions);

        $nonSensitivePermissions = $permissions->reject(
            fn (Permission $permission): bool => $this->isRestrictedPermission($permission->name),
        );

        $editorRole->syncPermissions($nonSensitivePermissions);

        $adminUser = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ],
        );

        $adminUser->syncRoles([$superAdminRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function isRestrictedPermission(string $permission): bool
    {
        $action = Str::of($permission)->before(':')->toString();
        $subject = Str::of($permission)->after(':')->toString();

        if ($subject === 'User') {
            return ! in_array($action, self::EDITOR_ALLOWED_USER_ACTIONS, true);
        }

        if (in_array($subject, self::RESTRICTED_PERMISSION_SUBJECTS, true)) {
            return true;
        }

        return in_array($subject, self::EDITOR_RESTRICTED_DESTRUCTIVE_SUBJECTS, true)
            && in_array($action, self::EDITOR_RESTRICTED_DESTRUCTIVE_ACTIONS, true);
    }
}
