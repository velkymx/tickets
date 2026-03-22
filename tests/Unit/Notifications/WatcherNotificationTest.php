<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\WatcherNotification;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WatcherNotificationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_sends_via_mail_channel(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Ticket', 'Test message', 'http://example.com/ticket/1');

        $this->assertSame(['mail', 'database'], $notification->via($user));
    }

    #[Test]
    public function it_builds_mail_message_with_subject(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Ticket', 'Test message', 'http://example.com/ticket/1');

        $mail = $notification->toMail($user);

        $this->assertNotNull($mail);
        $this->assertStringContainsString('Ticket Updated', $mail->subject ?? '');
    }

    #[Test]
    public function it_includes_action_button(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Ticket', 'Test message', 'http://example.com/ticket/1');

        $mail = $notification->toMail($user);

        $this->assertNotNull($mail);
        $this->assertStringContainsString('example.com/ticket/1', $mail->actionUrl);
        $this->assertNotEmpty($mail->actionText);
    }

    #[Test]
    public function it_includes_type_and_message_in_body(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Milestone', 'The Milestone "Sprint 1" has been updated.', 'http://example.com/milestone/1');

        $mail = $notification->toMail($user);

        $this->assertNotEmpty($mail->introLines);
        $this->assertStringContainsString('Sprint 1', $mail->introLines[0]);
    }

    #[Test]
    public function it_returns_structured_database_payload(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Ticket', 'Test message', 'http://example.com/ticket/1');

        $payload = $notification->toArray($user);

        $this->assertSame('watching', $payload['type']);
        $this->assertSame('Ticket', $payload['subject_type']);
        $this->assertSame('Test message', $payload['message']);
        $this->assertSame('http://example.com/ticket/1', $payload['url']);
    }
}
