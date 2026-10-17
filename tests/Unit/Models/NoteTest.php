<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NoteTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_reports_edited_resolved_and_superseded_states(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $original = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'edited_at' => now(),
            'resolved' => true,
            'notetype' => 'decision',
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'supersedes_id' => $original->id,
            'notetype' => 'decision',
        ]);

        $this->assertTrue($original->isEdited());
        $this->assertTrue($original->isResolved());
        $this->assertTrue($original->isSuperseded());
    }

    #[Test]
    public function it_groups_reactions_and_reports_current_user_state(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);
        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $otherUser->id,
            'emoji' => 'thumbsup',
        ]);
        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $otherUser->id,
            'emoji' => 'eyes',
        ]);

        $this->actingAs($user);

        $grouped = $note->fresh('reactions')->groupedReactions();

        $this->assertInstanceOf(Collection::class, $grouped);
        $this->assertSame(['count' => 2, 'reacted' => true], $grouped->get('thumbsup'));
        $this->assertSame(['count' => 1, 'reacted' => false], $grouped->get('eyes'));
        $this->assertTrue($note->hasReacted($user, 'thumbsup'));
        $this->assertFalse($note->hasReacted($user, 'eyes'));
    }

    #[Test]
    public function it_identifies_stale_blockers(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $staleBlocker = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => false,
            'created_at' => now()->subHours(49),
        ]);
        $resolvedBlocker = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => true,
            'created_at' => now()->subHours(72),
        ]);

        $this->assertTrue($staleBlocker->isStaleBlocker());
        $this->assertFalse($resolvedBlocker->isStaleBlocker());
    }

    #[Test]
    public function it_exposes_pinned_top_level_active_actions_and_active_blockers_scopes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $topLevel = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'pinned' => true,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $topLevel->id,
        ]);
        $activeAction = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'action',
            'resolved' => false,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'action',
            'resolved' => true,
        ]);
        $activeBlocker = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => true,
        ]);

        $this->assertTrue(Note::pinned()->get()->contains($topLevel));
        $this->assertTrue(Note::topLevel()->get()->contains($topLevel));
        $this->assertTrue(Note::activeActions()->get()->contains($activeAction));
        $this->assertTrue(Note::activeBlockers()->get()->contains($activeBlocker));
    }
}
