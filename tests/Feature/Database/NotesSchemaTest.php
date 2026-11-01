<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesSchemaTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function body_markdown_is_a_nullable_column(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'body_markdown'));

        $bodyMarkdown = collect(Schema::getColumns('notes'))->firstWhere('name', 'body_markdown');

        $this->assertNotNull($bodyMarkdown);
        $this->assertTrue($bodyMarkdown['nullable']);
    }

    #[Test]
    public function edited_at_is_a_nullable_column(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'edited_at'));

        $editedAt = collect(Schema::getColumns('notes'))->firstWhere('name', 'edited_at');

        $this->assertNotNull($editedAt);
        $this->assertTrue($editedAt['nullable']);
    }

    #[Test]
    public function notetype_defaults_to_message(): void
    {
        $notetype = collect(Schema::getColumns('notes'))->firstWhere('name', 'notetype');

        $this->assertNotNull($notetype);
        $this->assertSame('message', trim((string) $notetype['default'], "'"));
    }

    #[Test]
    public function parent_id_is_nullable_and_indexed(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'parent_id'));

        $indexes = collect(Schema::getIndexes('notes'))->pluck('name');

        $this->assertTrue(
            $indexes->contains('notes_parent_id_index'),
            'Failed asserting that the notes.parent_id index exists.'
        );
    }

    #[Test]
    public function deleting_a_parent_note_cascades_to_its_replies(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $parent = Note::factory()->create(['user_id' => $user->id, 'ticket_id' => $ticket->id]);
        $reply = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
        ]);

        $parent->delete();

        $this->assertDatabaseMissing('notes', ['id' => $reply->id]);
    }

    #[Test]
    public function status_and_decision_columns_carry_expected_defaults(): void
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
