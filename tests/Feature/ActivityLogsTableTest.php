<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_computed_columns_without_querying_missing_columns(): void
    {
        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);

        $avanti = Client::query()->create(['name' => 'Avanti Rahma', 'company' => 'Avanti Co', 'email' => 'avanti@example.test']);
        $other = Client::query()->create(['name' => 'Budi', 'company' => 'Budi Co', 'email' => 'budi@example.test']);

        $avantiLog = Activity::query()->where('subject_type', Client::class)->where('subject_id', $avanti->getKey())->firstOrFail();
        $otherLog = Activity::query()->where('subject_type', Client::class)->where('subject_id', $other->getKey())->firstOrFail();

        Livewire::test(ListActivityLogs::class)
            ->searchTable('avan')
            ->assertCanSeeTableRecords([$avantiLog])
            ->assertCanNotSeeTableRecords([$otherLog])
            ->searchTable('Client')
            ->assertCanSeeTableRecords([$avantiLog, $otherLog])
            ->sortTable('associated_record')
            ->assertSuccessful();
    }
}
