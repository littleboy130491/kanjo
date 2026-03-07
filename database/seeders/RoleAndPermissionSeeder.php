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
        'User',
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
        $everyAccessRole = Role::findOrCreate(UserRole::EveryAccess->value, 'web');
        $editorRole = Role::findOrCreate(UserRole::Editor->value, 'web');

        $superAdminRole->syncPermissions($permissions);

        $nonSensitivePermissions = $permissions->reject(
            fn (Permission $permission): bool => $this->isRestrictedPermission($permission->name),
        );

        $everyAccessRole->syncPermissions($nonSensitivePermissions);
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
        $subject = Str::of($permission)->after(':')->toString();

        return in_array($subject, self::RESTRICTED_PERMISSION_SUBJECTS, true);
    }
}
