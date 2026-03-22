<?php

namespace Tests\Unit\Models;

use App\Models\Mention;
use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $note = new Note;
        $fillable = $note->getFillable();

        $this->assertContains('body', $fillable);
        $this->assertContains('body_markdown', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
        $this->assertContains('parent_id', $fillable);
        $this->assertContains('hours', $fillable);
        $this->assertContains('notetype', $fillable);
        $this->assertContains('hide', $fillable);
        $this->assertContains('edited_at', $fillable);
        $this->assertContains('pinned', $fillable);
        $this->assertContains('resolved', $fillable);
        $this->assertContains('resolved_by', $fillable);
        $this->assertContains('supersedes_id', $fillable);
        $this->assertContains('resolution_message', $fillable);
    }

    #[Test]
    public function it_casts_hours_to_decimal(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => '7.50',
        ]);

        $this->assertEquals('7.50', $note->hours);
    }

    #[Test]
    public function it_casts_hide_to_boolean(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hide' => 1,
        ]);

        $this->assertIsBool($note->hide);
        $this->assertTrue($note->hide);
    }

    #[Test]
    public function it_casts_edited_at_pinned_and_resolved_fields(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'edited_at' => '2026-03-21 15:00:00',
            'pinned' => 1,
            'resolved' => 0,
        ]);

        $this->assertInstanceOf(Carbon::class, $note->edited_at);
        $this->assertTrue($note->pinned);
        $this->assertIsBool($note->pinned);
        $this->assertFalse($note->resolved);
        $this->assertIsBool($note->resolved);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Note Author']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $note->user);
        $this->assertEquals('Note Author', $note->user->name);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Test Ticket',
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $note->ticket);
        $this->assertEquals('Test Ticket', $note->ticket->subject);
    }

    #[Test]
    public function it_has_parent_and_reply_relationships(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
        $reply = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($reply->parent->is($parent));
        $this->assertTrue($parent->replies->first()->is($reply));
    }

    #[Test]
    public function it_has_reactions_attachments_and_mentions_relationships(): void
    {
        $user = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $reaction = NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);
        $attachment = NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'filename' => 'design.png',
            'path' => 'attachments/design.png',
            'mime_type' => 'image/png',
            'size' => 512,
        ]);
        $mention = Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);

        $this->assertTrue($note->reactions->first()->is($reaction));
        $this->assertTrue($note->attachments->first()->is($attachment));
        $this->assertTrue($note->mentions->first()->is($mention));
    }

    #[Test]
    public function it_has_resolved_by_and_supersession_relationships(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $original = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'decision',
        ]);
        $replacement = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'resolved_by' => $user->id,
            'supersedes_id' => $original->id,
        ]);

        $this->assertTrue($replacement->resolvedByUser->is($user));
        $this->assertTrue($replacement->supersedes->is($original));
        $this->assertTrue($original->supersededBy->is($replacement));
    }

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
