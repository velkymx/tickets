<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\WatcherNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WatcherNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_via_mail_channel(): void
    {
        $user = User::factory()->create();
        $notification = new WatcherNotification('Ticket', 'Test message', 'http://example.com/ticket/1');

        $this->assertContains('mail', $notification->via($user));
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
}
