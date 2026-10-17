<?php

namespace Tests\Feature\Services;

use App\Jobs\SendTicketDigestJob;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\ReplyNotification;
use App\Notifications\WatcherDatabaseNotification;
use App\Notifications\WatcherNotification;
use App\Services\NotificationBatchService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationPrefsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function disabled_mention_type_lands_in_app_only(): void
    {
        $user = User::factory()->create();
        $user->setNotificationPrefs(['mentions' => false]);
        $actor = User::factory()->create(['name' => 'Sarah']);

        app(NotificationBatchService::class)->dispatch(
            $user,
            new MentionNotification($actor->id, $actor->name, 142, 55, 'Hi there', 'http://example.com/tickets/142'),
            142
        );

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
        $this->assertNull(Cache::get(app(NotificationBatchService::class)->batchKey($user->id, 142)));
    }

    #[Test]
    public function enabled_mention_type_is_delivered_and_batched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        app(NotificationBatchService::class)->dispatch(
            $user,
            new MentionNotification($actor->id, $actor->name, 142, 55, 'Hi there', 'http://example.com/tickets/142'),
            142
        );

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
        $this->assertNotEmpty(Cache::get(app(NotificationBatchService::class)->batchKey($user->id, 142)));
        Queue::assertPushed(SendTicketDigestJob::class);
    }

    #[Test]
    public function immediate_mode_sends_mail_at_once(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->setNotificationPrefs(['email_mode' => 'immediate']);
        $actor = User::factory()->create(['name' => 'Sarah']);

        app(NotificationBatchService::class)->dispatch(
            $user,
            new MentionNotification($actor->id, $actor->name, 142, 55, 'Hi there', 'http://example.com/tickets/142'),
            142
        );

        Notification::assertSentTo(
            $user,
            MentionNotification::class,
            fn ($notification, $channels) => in_array('mail', $channels)
        );
        Notification::assertSentTo(
            $user,
            MentionNotification::class,
            fn ($notification, $channels) => in_array('database', $channels)
        );
        $this->assertNull(Cache::get(app(NotificationBatchService::class)->batchKey($user->id, 142)));
    }

    #[Test]
    public function profile_update_persists_prefs(): void
    {
        // Omitted prefs must not reset an explicit opt-out back to on.
        $user = User::factory()->create();
        $user->setNotificationPrefs(['mentions' => false]);

        $this->actingAs($user)->post('/user/update', [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'UTC',
            'theme' => 'auto',
        ]);

        $this->assertFalse($user->fresh()->notificationPrefs()['mentions']);
        $this->assertTrue($user->fresh()->notificationPrefs()['watching']);
    }

    #[Test]
    public function profile_update_can_disable_a_pref(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/update', [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'UTC',
            'theme' => 'auto',
            'notify_mentions' => '0',
            'email_mode' => 'immediate',
        ]);

        $this->assertFalse($user->fresh()->notificationPrefs()['mentions']);
        $this->assertSame('immediate', $user->fresh()->notificationPrefs()['email_mode']);
    }

    #[Test]
    public function disabled_reply_type_lands_in_app_only(): void
    {
        $user = User::factory()->create();
        $user->setNotificationPrefs(['replies' => false]);
        $actor = User::factory()->create(['name' => 'Alex']);

        app(NotificationBatchService::class)->dispatch(
            $user,
            new ReplyNotification($actor->id, $actor->name, 142, 55, 'Pushed a fix', 'http://example.com/tickets/142#note_55'),
            142
        );

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
        $this->assertNull(Cache::get(app(NotificationBatchService::class)->batchKey($user->id, 142)));
    }

    #[Test]
    public function unknown_type_fails_loud_outside_production(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $user->wantsNotification('carrier-pigeon');
    }

    #[Test]
    public function typeless_notification_fails_at_dispatch_not_in_digest(): void
    {
        $user = User::factory()->create();

        $typeless = new class extends \Illuminate\Notifications\Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return ['message' => 'No type key here'];
            }
        };

        $this->expectException(\InvalidArgumentException::class);

        app(NotificationBatchService::class)->dispatch($user, $typeless, 142);
    }

    #[Test]
    public function direct_watcher_path_honours_the_pref(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create();
        $author = User::factory()->create();
        $muted = User::factory()->create();
        $muted->setNotificationPrefs(['watching' => false]);
        $open = User::factory()->create();
        TicketUserWatcher::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $muted->id, 'muted' => true]);
        TicketUserWatcher::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $open->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id]);

        app(NotificationService::class)->notifyWatchers($ticket, $note);

        Notification::assertSentTo($muted, WatcherDatabaseNotification::class);
        Notification::assertSentTo($open, WatcherNotification::class);
    }
}
