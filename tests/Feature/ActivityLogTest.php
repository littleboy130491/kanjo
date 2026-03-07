<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_created_updated_and_deleted_activity_with_request_metadata(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->app['request']->headers->set('User-Agent', 'Codex Test Browser/1.0');
        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');

        $client = Client::query()->create([
            'name' => 'Jane Doe',
            'company' => 'Acme Co',
            'email' => 'jane@example.com',
            'phone' => '12345',
            'notes' => [['note' => 'first']],
        ]);

        $client->update([
            'phone' => '67890',
        ]);

        $client->delete();

        $activities = Activity::query()
            ->where('subject_type', Client::class)
            ->where('subject_id', $client->getKey())
            ->orderBy('id')
            ->get();

        $this->assertCount(3, $activities);
        $this->assertSame(['created', 'updated', 'deleted'], $activities->pluck('event')->all());
        $this->assertTrue($activities->every(fn (Activity $activity): bool => $activity->causer_id === $user->getKey()));
        $this->assertTrue($activities->every(fn (Activity $activity): bool => $activity->ip_address === '127.0.0.1'));
        $this->assertTrue($activities->every(fn (Activity $activity): bool => $activity->device === 'Codex Test Browser/1.0'));
    }

    public function test_it_does_not_log_excluded_user_attributes(): void
    {
        $user = User::factory()->create();

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayNotHasKey('password', $activity->changes()->get('attributes', []));
        $this->assertArrayNotHasKey('remember_token', $activity->changes()->get('attributes', []));
    }
}
