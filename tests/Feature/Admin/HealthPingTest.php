<?php

namespace Tests\Feature\Admin;

use App\Jobs\HealthPingJob;
use App\Models\User;
use App\SystemHealth\Checks\QueueCheck;
use App\SystemHealth\HealthStatus;
use Illuminate\Support\Facades\Cache;
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

        (new HealthPingJob('abc123'))->handle();

        $this->assertSame(['at' => now()->timestamp], Cache::get('health-ping:abc123'));
    }

    #[Test]
    public function status_is_pending_then_answered(): void
    {
        Cache::flush();
        $this->actingAs($this->admin())->getJson('/admin/health/queue-ping/abc123')->assertOk()->assertJson(['status' => 'pending']);

        Cache::put('health-ping-sent:abc123', now()->timestamp - 2, 30);
        (new HealthPingJob('abc123'))->handle();

        $response = $this->actingAs($this->admin())->getJson('/admin/health/queue-ping/abc123')->assertOk();

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
}
