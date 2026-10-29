<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Shield page permissions are not created by the resource seeder, which hid these pages from everyone.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'View:ManageAiChatbotSettings',
        'View:ManageTrackingSettings',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        Role::query()
            ->where('name', UserRole::SuperAdmin->value)
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep the permissions: they may have been granted to other roles since.
    }
};
