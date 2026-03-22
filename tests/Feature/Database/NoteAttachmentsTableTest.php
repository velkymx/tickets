<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoteAttachmentsTableTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_note_attachments_table_with_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('note_attachments', [
            'id',
            'note_id',
            'user_id',
            'ticket_id',
            'filename',
            'path',
            'mime_type',
            'size',
            'created_at',
            'updated_at',
        ]));

        $columns = collect(DB::select('PRAGMA table_info(note_attachments)'))->keyBy('name');
        $indexes = collect(DB::select('PRAGMA index_list(note_attachments)'));

        $this->assertSame(1, $columns['note_id']->notnull);
        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'note_attachments_note_id_index'),
            'Failed asserting that the note_attachments.note_id index exists.'
        );
        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'note_attachments_ticket_id_index'),
            'Failed asserting that the note_attachments.ticket_id index exists.'
        );
    }

    #[Test]
    public function deleting_related_records_cascades_to_note_attachments(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);

        $attachment = NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'filename' => 'spec.pdf',
            'path' => 'attachments/spec.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);

        $note->delete();

        $this->assertDatabaseMissing('note_attachments', ['id' => $attachment->id]);

        $secondNote = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
        $secondAttachment = NoteAttachment::create([
            'note_id' => $secondNote->id,
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'filename' => 'image.png',
            'path' => 'attachments/image.png',
            'mime_type' => 'image/png',
            'size' => 1024,
        ]);

        $ticket->delete();

        $this->assertDatabaseMissing('note_attachments', ['id' => $secondAttachment->id]);
    }
}
