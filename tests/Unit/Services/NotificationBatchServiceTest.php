<?php

namespace Tests\Unit\Services;

use App\Jobs\SendTicketDigestJob;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Services\NotificationBatchService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationBatchServiceTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_sends_database_notifications_immediately(): void
    {
        Notification::fake();
        Bus::fake();

        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor->id, $actor->name, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        app(NotificationBatchService::class)->dispatch($user, $notification, 142);

        Notification::assertSentTo($user, MentionNotification::class, function ($notification, $channels) {
            return $channels === ['database'];
        });
    }

    #[Test]
    public function it_queues_only_one_digest_job_per_user_and_ticket_within_the_batch_window(): void
    {
        Notification::fake();
        Bus::fake();
        Cache::flush();

        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor->id, $actor->name, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        $service = app(NotificationBatchService::class);
        $service->dispatch($user, $notification, 142);
        $service->dispatch($user, $notification, 142);

        Bus::assertDispatchedTimes(SendTicketDigestJob::class, 1);
    }

    #[Test]
    public function it_releases_the_lock_when_cache_put_throws(): void
    {
        Notification::fake();
        Cache::flush();

        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor->id, $actor->name, 142, 55, 'Check deploy', 'http://example.com/tickets/142');

        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->with(2);
        $lock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('ticket-notification-batch:'.$user->id.':142:lock', 5)
            ->andReturn($lock);
        Cache::shouldReceive('get')->once()->andReturn([]);
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('cache down'));

        try {
            app(NotificationBatchService::class)->dispatch($user, $notification, 142);
            $this->fail('Expected the cache failure to surface.');
        } catch (\RuntimeException $e) {
            $this->assertSame('cache down', $e->getMessage());
        }
    }
}
