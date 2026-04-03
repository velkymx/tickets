<?php

namespace Tests\Feature\Database;

use App\Models\Mention;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MentionsTableTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_creates_the_mentions_table_with_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('mentions', [
            'id',
            'note_id',
            'user_id',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(DB::select('PRAGMA index_list(mentions)'));

        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'mentions_note_id_user_id_unique'),
            'Failed asserting that the mentions unique index exists.'
        );
        $this->assertTrue(
            $indexes->contains(fn (object $index) => $index->name === 'mentions_user_id_index'),
            'Failed asserting that the mentions.user_id index exists.'
        );
    }

    #[Test]
    public function it_prevents_duplicate_mentions_for_the_same_note_and_user(): void
    {
        $author = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $author->id,
            'ticket_id' => $ticket->id,
        ]);

        Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);

        $this->expectException(QueryException::class);

        Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);
    }

    #[Test]
    public function deleting_a_note_or_user_cascades_to_mentions(): void
    {
        $author = User::factory()->create();
        $mentionedUser = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create([
            'user_id' => $author->id,
            'ticket_id' => $ticket->id,
        ]);

        $mention = Mention::create([
            'note_id' => $note->id,
            'user_id' => $mentionedUser->id,
        ]);

        $note->delete();

        $this->assertDatabaseMissing('mentions', ['id' => $mention->id]);

        $secondNote = Note::factory()->create([
            'user_id' => $author->id,
            'ticket_id' => $ticket->id,
        ]);
        $secondMention = Mention::create([
            'note_id' => $secondNote->id,
            'user_id' => $mentionedUser->id,
        ]);

        $mentionedUser->delete();

        $this->assertDatabaseMissing('mentions', ['id' => $secondMention->id]);
    }
}
