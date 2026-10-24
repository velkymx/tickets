<?php

namespace Tests\Feature\Admin;

use App\Jobs\HealthPingJob;
use App\Models\User;
use App\SystemHealth\Checks\QueueCheck;
use App\SystemHealth\HealthStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class HealthPingTest extends TestCase
{
    use SeedsDatabase;

    private function admin(): User
    {
        return User::factory()->create(['admin' => true]);
    }

    private function results(): array
    {
        $byLabel = [];

        foreach ((new QueueCheck)->run() as $result) {
            $byLabel[$result->label] = $result;
        }

        return $byLabel;
    }

    #[Test]
    public function ping_dispatches_one_job_per_pending_window(): void
    {
        Queue::fake();

        $first = $this->actingAs($this->admin())->postJson('/admin/health/queue-ping');
        $first->assertOk();
        $token = $first->json('token');
        $this->assertNotEmpty($token);

        Queue::assertPushed(HealthPingJob::class, fn ($job) => $job->token === $token);

        $this->actingAs($this->admin())->postJson('/admin/health/queue-ping')->assertOk()->assertJson(['token' => $token]);

        Queue::assertPushed(HealthPingJob::class, 1);
    }

    #[Test]
    public function job_writes_its_token_to_the_cache(): void
    {
        Cache::flush();

        (new HealthPingJob('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))->handle();

        $this->assertIsFloat(Cache::get('health-ping:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')['at']);
    }

    #[Test]
    public function status_is_pending_then_answered(): void
    {
        Cache::flush();
        $this->actingAs($this->admin())->getJson('/admin/health/queue-ping/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')->assertOk()->assertJson(['status' => 'pending']);

        Cache::put('health-ping-sent:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', now()->timestamp - 2, 30);
        (new HealthPingJob('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'))->handle();

        $response = $this->actingAs($this->admin())->getJson('/admin/health/queue-ping/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')->assertOk();

        $this->assertSame('answered', $response->json('status'));
        $this->assertGreaterThanOrEqual(1, $response->json('duration'));
        $this->assertLessThanOrEqual(5, $response->json('duration'));
    }

    #[Test]
    public function sync_connection_renders_a_warning(): void
    {
        config(['queue.default' => 'sync']);

        $this->assertSame(HealthStatus::Warning, $this->results()['Worker']->status);
    }

    #[Test]
    public function database_connection_without_jobs_table_is_a_problem(): void
    {
        config(['queue.default' => 'database']);
        config(['queue.connections.database.table' => 'missing_jobs_table']);

        $result = $this->results()['Queue backend'];

        $this->assertSame(HealthStatus::Problem, $result->status);
        $this->assertNotNull($result->fix);
    }

    #[Test]
    public function default_database_queue_has_its_jobs_table(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'jobs']);

        $this->assertSame(HealthStatus::Ok, $this->results()['Queue backend']->status);
    }

    #[Test]
    public function reserved_jobs_are_not_stale(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'jobs']);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 1,
            'reserved_at' => now()->timestamp,
            'available_at' => now()->timestamp - 600,
            'created_at' => now()->timestamp - 600,
        ]);

        $this->assertSame(HealthStatus::Ok, $this->results()['Pending jobs']->status);
    }

    #[Test]
    public function unreserved_old_jobs_are_stale(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'jobs']);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp - 600,
            'created_at' => now()->timestamp - 600,
        ]);

        $this->assertSame(HealthStatus::Warning, $this->results()['Pending jobs']->status);
    }

    #[Test]
    public function answered_status_reports_connection_and_queue(): void
    {
        $token = str_repeat('b', 32);
        Cache::put("health-ping-sent:{$token}", microtime(true) - 1.5, 30);
        (new HealthPingJob($token))->handle();

        $this->actingAs($this->admin())->getJson("/admin/health/queue-ping/{$token}")
            ->assertOk()
            ->assertJson([
                'status' => 'answered',
                'connection' => (string) config('queue.default'),
            ])
            ->assertJsonStructure(['duration', 'queue']);
    }

    #[Test]
    public function ping_reports_backend_failure_and_clears_pending(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.table' => 'missing_jobs_table',
        ]);

        $this->actingAs($this->admin())->postJson('/admin/health/queue-ping')
            ->assertStatus(500)
            ->assertJsonStructure(['error']);

        $this->assertNull(Cache::get('health-ping-pending'));
    }

    #[Test]
    public function malformed_ping_token_is_not_found(): void
    {
        $this->actingAs($this->admin())->getJson('/admin/health/queue-ping/abc')->assertNotFound();
    }
}
