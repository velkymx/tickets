<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendTicketDigestJob;
use App\Models\User;
use App\Notifications\TicketDigestNotification;
use App\Services\NotificationBatchService;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTicketDigestJobTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_sends_a_single_digest_notification_and_clears_the_batch(): void
    {
        Notification::fake();
        Cache::flush();

        $user = User::factory()->create();
        $key = "ticket-notification-batch:{$user->id}:142";

        Cache::put($key, [
            [
                'type' => 'mention',
                'subject' => 'Sarah mentioned you in Ticket #142',
                'excerpt' => 'Can you check the deploy?',
                'url' => 'http://example.com/tickets/142',
                'created_at' => now()->toIso8601String(),
            ],
            [
                'type' => 'reply',
                'subject' => 'Alex replied to your comment in Ticket #142',
                'excerpt' => 'I pushed the fix.',
                'url' => 'http://example.com/tickets/142#note_55',
                'created_at' => now()->toIso8601String(),
            ],
        ], now()->addMinutes(10));

        (new SendTicketDigestJob($user->id, 142))->handle(app(NotificationBatchService::class));

        Notification::assertSentTo($user, TicketDigestNotification::class);
        $this->assertNull(Cache::get($key));
    }

    #[Test]
    public function it_handles_empty_entries_without_crashing(): void
    {
        $notification = new TicketDigestNotification(142, []);

        $mail = $notification->toMail(User::factory()->make());

        $this->assertNotNull($mail);
    }
}
