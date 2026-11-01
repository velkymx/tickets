<?php

namespace Tests\Unit;

use App\Models\Mention;
use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NoteTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function a_note_can_have_a_parent_note()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $parent = Note::create([
            'body' => 'Parent note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $reply = Note::create([
            'body' => 'Reply note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'notetype' => 'message',
        ]);

        $this->assertEquals($parent->id, $reply->parent_id);
        $this->assertTrue($reply->parent->is($parent));
    }

    #[Test]
    public function it_can_have_reactions()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $reaction = NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);

        $this->assertCount(1, $note->reactions);
        $this->assertTrue($note->reactions->first()->is($reaction));
    }

    #[Test]
    public function it_can_have_attachments()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $attachment = NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'filename' => 'test.jpg',
            'path' => 'attachments/1/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->assertCount(1, $note->attachments);
        $this->assertTrue($note->attachments->first()->is($attachment));
    }

    #[Test]
    public function it_can_have_mentions()
    {
        $user = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $note = Note::create([
            'body' => "Hello @{$mentionedUser->name}",
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $mention = Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);

        $this->assertCount(1, $note->mentions);
        $this->assertTrue($note->mentions->first()->is($mention));
        $this->assertTrue($note->mentions->first()->user->is($mentionedUser));
    }

    #[Test]
    public function it_can_be_resolved()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
            'resolved' => true,
            'resolved_by' => $user->id,
            'resolution_message' => 'Resolved!',
        ]);

        $this->assertTrue($note->isResolved());
        $this->assertTrue($note->resolvedByUser->is($user));
    }

    #[Test]
    public function it_can_be_superseded()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $original = Note::create([
            'body' => 'Original',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
        ]);

        $supersededBy = Note::create([
            'body' => 'New',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
            'supersedes_id' => $original->id,
        ]);

        $this->assertTrue($original->isSuperseded());
        $this->assertTrue($supersededBy->supersedes->is($original));
    }

    #[Test]
    public function it_can_be_a_stale_blocker()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $staleNote = Note::create([
            'body' => 'Stale blocker',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
        ]);
        $staleNote->created_at = now()->subHours(49);
        $staleNote->save();

        $freshNote = Note::create([
            'body' => 'Fresh blocker',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
        ]);
        $freshNote->created_at = now()->subHours(47);
        $freshNote->save();

        $this->assertTrue($staleNote->isStaleBlocker());
        $this->assertFalse($freshNote->isStaleBlocker());
    }

    #[Test]
    public function it_has_useful_scopes()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        Note::create(['body' => 'Pinned', 'user_id' => $user->id, 'ticket_id' => $ticket->id, 'pinned' => true]);
        Note::create(['body' => 'Not pinned', 'user_id' => $user->id, 'ticket_id' => $ticket->id, 'pinned' => false]);

        $action = Note::create(['body' => 'Active Action', 'user_id' => $user->id, 'ticket_id' => $ticket->id, 'notetype' => 'action', 'resolved' => false]);
        Note::create(['body' => 'Resolved Action', 'user_id' => $user->id, 'ticket_id' => $ticket->id, 'notetype' => 'action', 'resolved' => true]);

        $this->assertCount(1, Note::pinned()->get());
        $this->assertCount(1, Note::activeActions()->get());
        $this->assertTrue(Note::activeActions()->first()->is($action));
    }

    #[Test]
    public function decision_urgency_escalates_with_wait_time()
    {
        $note = Note::factory()->make(['notetype' => 'decision_needed']);
        $note->created_at = now()->subHours(2);
        $this->assertSame('info', $note->decisionUrgency());

        $note->created_at = now()->subHours(Note::DECISION_WARNING_HOURS);
        $this->assertSame('warning', $note->decisionUrgency());

        $note->created_at = now()->subHours(Note::DECISION_DANGER_HOURS);
        $this->assertSame('danger', $note->decisionUrgency());
    }

    #[Test]
    public function awaiting_decision_from_returns_open_requests_for_that_user_only()
    {
        $decider = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $open = Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'decision_needed', 'decider_id' => $decider->id]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'decision_needed', 'decider_id' => $decider->id, 'resolved' => true]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'decision_needed', 'decider_id' => $decider->id, 'hide' => true]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'decision_needed', 'decider_id' => $other->id]);

        $found = Note::awaitingDecisionFrom($decider->id)->get();

        $this->assertCount(1, $found);
        $this->assertTrue($found->first()->is($open));
    }
}
