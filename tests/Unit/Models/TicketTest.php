<?php

namespace Tests\Unit\Models;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\Type;
use App\Models\User;
use App\Notifications\WatcherNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketTest extends TestCase
{
    use SeedsDatabase;

    protected Status $openStatus;

    protected Status $closedStatus;

    protected Type $type;

    protected Importance $importance;

    protected Project $project;

    protected Milestone $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = Status::factory()->create(['name' => 'New']);
        $this->closedStatus = Status::factory()->closed()->create();
        $this->type = Type::factory()->create(['name' => 'Bug']);
        $this->importance = Importance::factory()->create(['name' => 'Critical']);
        $this->project = Project::factory()->create(['name' => 'Test Project']);
        $this->milestone = Milestone::factory()->create(['name' => 'Sprint 1']);
    }

    #[Test]
    public function it_computes_actual_hours_from_notes_sum(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 2.5,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 3.5,
        ]);

        $loadedTicket = Ticket::withSum('notes', 'hours')->find($ticket->id);
        $this->assertEquals(6, $loadedTicket->actual_hours);
    }

    #[Test]
    public function it_returns_zero_actual_hours_when_not_eager_loaded(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 2,
        ]);

        $this->assertEquals(0, $ticket->actual_hours);
    }

    #[Test]
    public function it_notifies_watchers_on_update(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $watcher = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $creator->id,
            'user_id2' => $creator->id,
        ]);

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $watcher->id,
        ]);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertSentTo($watcher, WatcherNotification::class);
    }

    #[Test]
    public function it_does_not_notify_the_acting_user_on_update(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $otherWatcher = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $actor->id,
            'user_id2' => $actor->id,
        ]);

        // Both the actor and another user are watching
        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
        ]);
        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherWatcher->id,
        ]);

        $this->actingAs($actor);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertSentTo($otherWatcher, WatcherNotification::class);
        Notification::assertNotSentTo($actor, WatcherNotification::class);
    }

    #[Test]
    public function it_skips_watchers_with_no_user(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $creator->id,
            'user_id2' => $creator->id,
        ]);

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => 99999,
        ]);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_sets_closed_at_when_moved_to_completed(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $this->openStatus->id,
            'closed_at' => null,
        ]);

        $ticket->status_id = $this->closedStatus->id;
        $ticket->save();

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    #[Test]
    public function it_clears_closed_at_and_audits_when_reopened(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $this->closedStatus->id,
            'closed_at' => now(),
        ]);

        $ticket->status_id = $this->openStatus->id;
        $ticket->save();

        $this->assertNull($ticket->fresh()->closed_at);
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
        $note = Note::where('ticket_id', $ticket->id)
            ->where('notetype', 'changelog')
            ->latest('id')
            ->first();
        $this->assertStringContainsString('reopened', strtolower($note->body_markdown));
    }

    #[Test]
    public function it_keeps_explicit_closed_at_when_closing(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $this->openStatus->id,
            'closed_at' => null,
        ]);

        $ticket->status_id = $this->closedStatus->id;
        $ticket->closed_at = '2026-01-15 10:00:00';
        $ticket->save();

        $this->assertEquals(
            '2026-01-15 10:00:00',
            $ticket->fresh()->closed_at->format('Y-m-d H:i:s')
        );
    }
}
