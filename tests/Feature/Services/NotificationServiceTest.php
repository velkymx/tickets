<?php

namespace Tests\Feature\Services;

use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\User;
use App\Notifications\WatcherDatabaseNotification;
use App\Notifications\WatcherNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationServiceTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_does_not_email_the_comment_author(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $watcher = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);

        TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $author->id]);
        TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $watcher->id]);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'body' => 'A comment',
        ]);

        $service = app(NotificationService::class);
        $service->notifyWatchers($ticket, $note);

        Notification::assertSentTo($watcher, WatcherNotification::class);
        Notification::assertNotSentTo($author, WatcherNotification::class);
    }

    #[Test]
    public function it_skips_watcher_notification_for_mentioned_users(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $mentioned = User::factory()->create();
        $otherWatcher = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);

        TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $mentioned->id]);
        TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $otherWatcher->id]);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'body' => '@'.$mentioned->name,
        ]);

        Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentioned->id,
        ]);

        $service = app(NotificationService::class);
        $service->notifyWatchers($ticket, $note);

        // Mentioned users already get MentionNotification, so skip watcher notification entirely
        Notification::assertNotSentTo($mentioned, WatcherNotification::class);
        Notification::assertNotSentTo($mentioned, WatcherDatabaseNotification::class);
        // Non-mentioned watchers still get notified
        Notification::assertSentTo($otherWatcher, WatcherNotification::class);
    }

    #[Test]
    public function it_suppresses_email_for_active_viewers(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);

        TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $viewer->id]);

        // Viewer was active within last 2 minutes
        TicketView::create([
            'user_id' => $viewer->id,
            'ticket_id' => $ticket->id,
        ]);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        $service = app(NotificationService::class);
        $service->notifyWatchers($ticket, $note);

        // Should still get database notification but email suppressed
        // We check that a DatabaseOnly notification was sent instead
        Notification::assertSentTo($viewer, WatcherDatabaseNotification::class);
        Notification::assertNotSentTo($viewer, WatcherNotification::class);
    }

    #[Test]
    public function it_respects_muted_watchers(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $muted = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);

        TicketUserWatcher::create([
            'ticket_id' => $ticket->id,
            'user_id' => $muted->id,
            'muted' => true,
        ]);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        $service = app(NotificationService::class);
        $service->notifyWatchers($ticket, $note);

        // Muted watchers get database only, no email
        Notification::assertSentTo($muted, WatcherDatabaseNotification::class);
        Notification::assertNotSentTo($muted, WatcherNotification::class);
    }
}
