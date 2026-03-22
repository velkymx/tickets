<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
    public function it_can_have_new_notetype_values()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $types = ['decision', 'blocker', 'update', 'action'];

        foreach ($types as $type) {
            $note = Note::create([
                'body' => "Test $type",
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'notetype' => $type,
            ]);

            $this->assertEquals($type, $note->notetype);
        }
    }

    #[Test]
    public function it_can_have_an_edited_at_timestamp()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $now = now()->toDateTimeString();

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
            'edited_at' => $now,
        ]);

        $this->assertEquals($now, $note->edited_at->toDateTimeString());
    }

    #[Test]
    public function it_can_have_status_and_decision_fields()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $originalNote = Note::create([
            'body' => 'Original decision',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
        ]);

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
            'pinned' => true,
            'resolved' => true,
            'resolved_by' => $user->id,
            'supersedes_id' => $originalNote->id,
            'resolution_message' => 'Thread resolved here.',
        ]);

        $this->assertTrue($note->pinned);
        $this->assertTrue($note->resolved);
        $this->assertEquals($user->id, $note->resolved_by);
        $this->assertEquals($originalNote->id, $note->supersedes_id);
        $this->assertEquals('Thread resolved here.', $note->resolution_message);
    }

    #[Test]
    public function it_can_store_markdown_body()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $markdown = "## Title\n* list item";

        $note = Note::create([
            'body' => '<h2>Title</h2><ul><li>list item</li></ul>',
            'body_markdown' => $markdown,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $this->assertEquals($markdown, $note->body_markdown);
        $this->assertEquals('<h2>Title</h2><ul><li>list item</li></ul>', $note->body);
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

        $reaction = \App\Models\NoteReaction::create([
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

        $attachment = \App\Models\NoteAttachment::create([
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

        $mention = \App\Models\Mention::create([
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
}
