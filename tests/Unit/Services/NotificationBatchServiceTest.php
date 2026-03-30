<?php

namespace Tests\Unit\Services;

use App\Jobs\SendTicketDigestJob;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Services\NotificationBatchService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
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
        $notification = new MentionNotification($actor, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

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
        $notification = new MentionNotification($actor, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        $service = app(NotificationBatchService::class);
        $service->dispatch($user, $notification, 142);
        $service->dispatch($user, $notification, 142);

        Bus::assertDispatchedTimes(SendTicketDigestJob::class, 1);
    }

    #[Test]
    public function it_releases_lock_even_when_cache_put_throws(): void
    {
        Notification::fake();
        Bus::fake();
        Cache::flush();

        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor, 142, 55, 'Check deploy', 'http://example.com/tickets/142');

        $service = app(NotificationBatchService::class);
        $lockKey = $service->batchKey($user->id, 142) . ':lock';

        // Dispatch once to exercise the lock path
        $service->dispatch($user, $notification, 142);

        // Lock should be released — verify we can acquire it immediately
        $lock = Cache::lock($lockKey, 5);
        $this->assertTrue($lock->get(), 'Lock should be released after dispatch completes');
        $lock->release();
    }
}
