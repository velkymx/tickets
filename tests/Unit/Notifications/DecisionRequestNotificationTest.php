<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\DecisionRequestNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DecisionRequestNotificationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_mails_unless_mention_mail_is_off(): void
    {
        $user = User::factory()->create();
        $notification = $this->notification();

        $this->assertSame(['database', 'mail'], $notification->via($user));

        $user->setNotificationPrefs(['mentions' => false]);

        $this->assertSame(['database'], $notification->via($user->fresh()));
    }

    #[Test]
    public function it_builds_the_expected_mail_subject(): void
    {
        $mail = $this->notification()->toMail(new User);

        $this->assertSame('Sarah needs your decision on Ticket #142', $mail->subject);
    }

    #[Test]
    public function it_returns_structured_database_payload(): void
    {
        $payload = $this->notification()->toArray(new User);

        $this->assertSame('decision_request', $payload['type']);
        $this->assertSame(142, $payload['ticket_id']);
        $this->assertSame(55, $payload['note_id']);
        $this->assertSame('Decision needed: Ship Friday?', $payload['excerpt']);
    }

    private function notification(): DecisionRequestNotification
    {
        return new DecisionRequestNotification(7, 'Sarah', 142, 55, 'Ship Friday?', 'http://example.com/tickets/142');
    }
}
