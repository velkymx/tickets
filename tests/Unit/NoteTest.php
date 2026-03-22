<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
}
