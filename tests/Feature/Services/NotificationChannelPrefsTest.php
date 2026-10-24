<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationChannelPrefsTest extends TestCase
{
    use SeedsDatabase;

    private function mention(User $actor): MentionNotification
    {
        return new MentionNotification($actor->id, $actor->name, 142, 55, 'Hi there', 'http://example.com/tickets/142');
    }

    #[Test]
    public function defaults_send_both_channels(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $actor = User::factory()->create();

        $user->notify($this->mention($actor));

        Notification::assertSentTo(
            $user,
            MentionNotification::class,
            fn ($notification, $channels) => in_array('mail', $channels) && in_array('database', $channels)
        );
    }

    #[Test]
    public function email_disabled_keeps_database_only(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setNotificationPrefs(['email_enabled' => false]);
        $actor = User::factory()->create();

        $user->notify($this->mention($actor));

        Notification::assertSentTo(
            $user,
            MentionNotification::class,
            fn ($notification, $channels) => $channels === ['database']
        );
    }

    #[Test]
    public function category_off_keeps_database_only(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setNotificationPrefs(['mentions' => false]);
        $actor = User::factory()->create();

        $user->notify($this->mention($actor));

        Notification::assertSentTo(
            $user,
            MentionNotification::class,
            fn ($notification, $channels) => $channels === ['database']
        );
    }
}
