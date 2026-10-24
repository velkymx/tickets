<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesStatusAndDecisionFieldsMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_status_and_decision_columns_to_notes(): void
    {
        $this->assertTrue(Schema::hasColumns('notes', [
            'pinned',
            'resolved',
            'resolved_by',
            'supersedes_id',
            'resolution_message',
        ]));

        $columns = collect(Schema::getColumns('notes'))->keyBy('name');

        $this->assertSame('0', trim((string) $columns['pinned']['default'], "'"));
        $this->assertSame('0', trim((string) $columns['resolved']['default'], "'"));
        foreach (['resolved_by', 'supersedes_id', 'resolution_message'] as $column) {
            $default = $columns[$column]['default'];
            $this->assertTrue(
                $default === null || strtoupper((string) $default) === 'NULL',
                "notes.{$column} should default to NULL, got ".var_export($default, true)
            );
        }
    }

    #[Test]
    public function deleting_related_records_sets_resolved_by_and_supersedes_id_to_null(): void
    {
        $resolver = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $originalDecision = Note::factory()->create([
            'user_id' => $resolver->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
        ]);

        $note = Note::factory()->create([
            'user_id' => $resolver->id,
            'ticket_id' => $ticket->id,
            'resolved_by' => $resolver->id,
            'supersedes_id' => $originalDecision->id,
        ]);

        $resolver->delete();
        $originalDecision->delete();

        $note->refresh();

        $this->assertNull($note->resolved_by);
        $this->assertNull($note->supersedes_id);
    }
}
