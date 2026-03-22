<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MentionNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_via_mail_and_database_channels(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        $this->assertSame(['mail', 'database'], $notification->via($user));
    }

    #[Test]
    public function it_builds_the_expected_mail_subject(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        $mail = $notification->toMail($user);

        $this->assertSame('Sarah mentioned you in Ticket #142', $mail->subject);
    }

    #[Test]
    public function it_returns_structured_database_payload(): void
    {
        $actor = User::factory()->create(['name' => 'Sarah']);
        $notification = new MentionNotification($actor, 142, 55, 'Can you check the deploy?', 'http://example.com/tickets/142');

        $payload = $notification->toArray(new User);

        $this->assertSame('mention', $payload['type']);
        $this->assertSame(142, $payload['ticket_id']);
        $this->assertSame(55, $payload['note_id']);
        $this->assertSame('Sarah', $payload['actor_name']);
        $this->assertSame('Can you check the deploy?', $payload['excerpt']);
        $this->assertSame('http://example.com/tickets/142', $payload['url']);
    }
}
