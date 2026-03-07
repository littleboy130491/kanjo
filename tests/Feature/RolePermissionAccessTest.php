<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Admin\Resources\Clients\ClientResource;
use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Portfolios\PortfolioResource;
use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_permission_seeder_creates_roles_and_assigns_the_seeded_admin_as_super_admin(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => UserRole::SuperAdmin->value]);
        $this->assertDatabaseHas('roles', ['name' => UserRole::EveryAccess->value]);
        $this->assertDatabaseHas('roles', ['name' => UserRole::Editor->value]);

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole(UserRole::SuperAdmin->value));
    }

    public function test_super_admin_can_access_sensitive_and_operational_resources(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(ProposalContentDefaultResource::canViewAny());
        $this->assertTrue(ActivityLogResource::canViewAny());
        $this->assertTrue(RoleResource::canViewAny());
        $this->assertTrue(ProposalResource::canViewAny());
        $this->assertTrue(InvoiceResource::canViewAny());
        $this->assertTrue(ClientResource::canViewAny());
        $this->assertTrue(ServiceResource::canViewAny());
        $this->assertTrue(PortfolioResource::canViewAny());
    }

    public function test_every_access_and_editor_cannot_access_sensitive_resources_but_can_access_operational_resources(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        foreach ([UserRole::EveryAccess, UserRole::Editor] as $role) {
            $user = User::factory()->create();
            $user->assignRole(Role::findByName($role->value, 'web'));

            $this->actingAs($user);

            $this->assertFalse(UserResource::canViewAny(), $role->value.' should not access users');
            $this->assertFalse(CompanyResource::canViewAny(), $role->value.' should not access companies');
            $this->assertFalse(ProposalContentDefaultResource::canViewAny(), $role->value.' should not access proposal content defaults');
            $this->assertFalse(ActivityLogResource::canViewAny(), $role->value.' should not access activity logs');
            $this->assertFalse(RoleResource::canViewAny(), $role->value.' should not access roles');

            $this->assertTrue(ProposalResource::canViewAny(), $role->value.' should access proposals');
            $this->assertTrue(InvoiceResource::canViewAny(), $role->value.' should access invoices');
            $this->assertTrue(ClientResource::canViewAny(), $role->value.' should access clients');
            $this->assertTrue(ServiceResource::canViewAny(), $role->value.' should access services');
            $this->assertTrue(PortfolioResource::canViewAny(), $role->value.' should access portfolios');
        }
    }

    public function test_super_admin_can_manage_roles_from_the_filament_user_resource(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $editor = User::factory()->create();
        $editor->assignRole(Role::findByName(UserRole::Editor->value, 'web'));

        $this->actingAs($admin);

        Livewire::test(CreateUser::class)
            ->assertFormFieldExists('roles');

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $editor])
            ->assertTableColumnExists('roles.name')
            ->assertTableColumnFormattedStateSet('roles.name', UserRole::Editor->value, $editor);
    }
}
