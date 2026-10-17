<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesEditedAtMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_a_nullable_edited_at_column_to_notes(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'edited_at'));

        $columns = collect(Schema::getColumns('notes'));
        $editedAt = $columns->firstWhere('name', 'edited_at');

        $this->assertNotNull($editedAt);
        $this->assertTrue($editedAt['nullable']);
        $this->assertTrue($editedAt['default'] === null || strtoupper((string) $editedAt['default']) === 'NULL');
    }

    #[Test]
    public function edited_at_is_cast_to_a_datetime(): void
    {
        $note = Note::factory()->create([
            'edited_at' => '2026-03-21 12:34:56',
        ]);

        $this->assertSame('2026-03-21 12:34:56', $note->fresh()->edited_at?->toDateTimeString());
    }
}
