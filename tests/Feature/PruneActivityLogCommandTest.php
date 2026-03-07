<?php

namespace Tests\Feature;

use App\Services\ActivityLogPruner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PruneActivityLogCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_activity_logs_using_the_default_threshold(): void
    {
        Activity::query()->create([
            'log_name' => 'activity',
            'description' => 'created',
            'event' => 'created',
            'created_at' => Carbon::now()->subDays(31),
            'updated_at' => Carbon::now()->subDays(31),
        ]);

        Activity::query()->create([
            'log_name' => 'activity',
            'description' => 'updated',
            'event' => 'updated',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        $this->artisan('activity-log:prune')
            ->expectsOutputToContain('Deleted 1 activity log record(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_it_prunes_activity_logs_using_a_custom_threshold(): void
    {
        Activity::query()->create([
            'log_name' => 'activity',
            'description' => 'created',
            'event' => 'created',
            'created_at' => Carbon::now()->subDays(90),
            'updated_at' => Carbon::now()->subDays(90),
        ]);

        Activity::query()->create([
            'log_name' => 'activity',
            'description' => 'updated',
            'event' => 'updated',
            'created_at' => Carbon::now()->subDays(45),
            'updated_at' => Carbon::now()->subDays(45),
        ]);

        $this->artisan('activity-log:prune 60')
            ->expectsOutputToContain('Deleted 1 activity log record(s)')
            ->assertSuccessful();

        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_the_pruner_service_deletes_logs_at_or_beyond_the_cutoff(): void
    {
        Activity::query()->create([
            'log_name' => 'activity',
            'description' => 'created',
            'event' => 'created',
            'created_at' => Carbon::now()->subDays(365),
            'updated_at' => Carbon::now()->subDays(365),
        ]);

        $deletedCount = app(ActivityLogPruner::class)->pruneOlderThanDays(365);

        $this->assertSame(1, $deletedCount);
        $this->assertDatabaseCount('activity_log', 0);
    }
}
