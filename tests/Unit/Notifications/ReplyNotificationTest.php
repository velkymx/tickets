<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\ReplyNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ReplyNotificationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_sends_via_mail_and_database_channels(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new ReplyNotification($actor->id, $actor->name,142, 55, 'I pushed the fix.', 'http://example.com/tickets/142#note_55');

        $this->assertSame(['mail', 'database'], $notification->via($user));
    }

    #[Test]
    public function it_builds_the_expected_mail_subject(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new ReplyNotification($actor->id, $actor->name,142, 55, 'I pushed the fix.', 'http://example.com/tickets/142#note_55');

        $mail = $notification->toMail($user);

        $this->assertSame('Sarah replied to your comment in Ticket #142', $mail->subject);
    }

    #[Test]
    public function it_returns_structured_database_payload(): void
    {
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new ReplyNotification($actor->id, $actor->name,142, 55, 'I pushed the fix.', 'http://example.com/tickets/142#note_55');

        $payload = $notification->toArray(new User);

        $this->assertSame('reply', $payload['type']);
        $this->assertSame(142, $payload['ticket_id']);
        $this->assertSame(55, $payload['note_id']);
        $this->assertSame('Sarah', $payload['actor_name']);
        $this->assertSame('I pushed the fix.', $payload['excerpt']);
        $this->assertSame('http://example.com/tickets/142#note_55', $payload['url']);
    }
}
