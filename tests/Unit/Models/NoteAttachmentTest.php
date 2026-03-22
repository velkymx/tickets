<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteAttachmentTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_the_expected_fillable_fields(): void
    {
        $attachment = new NoteAttachment;

        $this->assertSame([
            'note_id',
            'user_id',
            'ticket_id',
            'filename',
            'path',
            'mime_type',
            'size',
        ], $attachment->getFillable());
    }

    #[Test]
    public function it_belongs_to_a_note_and_user(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $attachment = NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'filename' => 'artifact.png',
            'path' => 'attachments/artifact.png',
            'mime_type' => 'image/png',
            'size' => 4096,
        ]);

        $this->assertTrue($attachment->note->is($note));
        $this->assertTrue($attachment->user->is($user));
    }

    #[Test]
    public function it_exposes_a_url_and_image_flag_accessor(): void
    {
        $attachment = new NoteAttachment([
            'path' => 'attachments/diagram.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertStringEndsWith('/attachments/diagram.png', $attachment->url);
        $this->assertTrue($attachment->is_image);
        $this->assertFalse((new NoteAttachment(['mime_type' => 'application/pdf']))->is_image);
    }
}
