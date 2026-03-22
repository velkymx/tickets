<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotesNotetypeMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_defaults_notetype_to_message(): void
    {
        $columns = collect(DB::select('PRAGMA table_info(notes)'));
        $notetype = $columns->firstWhere('name', 'notetype');

        $this->assertNotNull($notetype);
        $this->assertSame("'message'", $notetype->dflt_value);
    }

    #[Test]
    public function it_supports_all_comment_note_types(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        foreach (['message', 'changelog', 'misc', 'decision', 'blocker', 'update', 'action'] as $type) {
            $note = Note::factory()->create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'notetype' => $type,
            ]);

            $this->assertSame($type, $note->fresh()->notetype);
        }
    }
}
