<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NoteReactionsTableTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_creates_the_note_reactions_table_with_expected_columns_and_unique_index(): void
    {
        $this->assertTrue(Schema::hasColumns('note_reactions', [
            'id',
            'note_id',
            'user_id',
            'emoji',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(DB::select('PRAGMA index_list(note_reactions)'));

        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'note_reactions_note_id_user_id_emoji_unique'),
            'Failed asserting that the note_reactions unique index exists.'
        );
    }

    #[Test]
    public function it_prevents_duplicate_reactions_for_the_same_note_user_and_emoji(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);

        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);

        $this->expectException(QueryException::class);

        NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);
    }

    #[Test]
    public function deleting_a_note_or_user_cascades_to_note_reactions(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);

        $reaction = NoteReaction::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'emoji' => 'eyes',
        ]);

        $note->delete();

        $this->assertDatabaseMissing('note_reactions', ['id' => $reaction->id]);

        $secondNote = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
        $secondReaction = NoteReaction::create([
            'note_id' => $secondNote->id,
            'user_id' => $user->id,
            'emoji' => 'thumbsup',
        ]);

        $user->delete();

        $this->assertDatabaseMissing('note_reactions', ['id' => $secondReaction->id]);
    }

    #[Test]
    public function note_reaction_model_limits_v21_emojis(): void
    {
        $this->assertSame(['thumbsup', 'eyes'], NoteReaction::ALLOWED_EMOJIS);
    }
}
