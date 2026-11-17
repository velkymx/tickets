<?php

namespace Tests\Feature\Services;

use App\Jobs\SendTicketDigestJob;
use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\User;
use App\Notifications\TicketDigestNotification;
use App\Notifications\WatcherDatabaseNotification;
use App\Notifications\WatcherNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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

    #[Test]
    public function muted_watchers_never_enter_the_email_digest(): void
    {
        Notification::fake();
        Queue::fake();

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

        app(NotificationService::class)->notifyWatchers($ticket, $note);

        Notification::assertSentTo($muted, WatcherDatabaseNotification::class);
        Notification::assertNotSentTo($muted, TicketDigestNotification::class);
        Queue::assertNotPushed(SendTicketDigestJob::class);
    }

    #[Test]
    public function active_viewers_are_loaded_once_for_many_watchers(): void
    {
        Notification::fake();

        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);

        foreach (range(1, 3) as $i) {
            $watcher = User::factory()->create();
            TicketUserWatcher::create(['ticket_id' => $ticket->id, 'user_id' => $watcher->id]);

            if ($i === 1) {
                TicketView::create(['user_id' => $watcher->id, 'ticket_id' => $ticket->id]);
            }
        }

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
        ]);

        DB::enableQueryLog();
        app(NotificationService::class)->notifyWatchers($ticket, $note);

        $viewQueries = collect(DB::getQueryLog())->filter(
            fn ($entry) => str_contains($entry['query'], 'ticket_views')
        );

        $this->assertCount(1, $viewQueries);
    }
}
