<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesParentIdMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_a_nullable_parent_id_column_with_an_index(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'parent_id'));

        $indexes = collect(DB::select('PRAGMA index_list(notes)'));

        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'notes_parent_id_index'),
            'Failed asserting that the notes.parent_id index exists.'
        );
    }

    #[Test]
    public function deleting_a_parent_note_cascades_to_its_replies(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $parent = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);

        $reply = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
        ]);

        $parent->delete();

        $this->assertDatabaseMissing('notes', ['id' => $reply->id]);
    }
}
