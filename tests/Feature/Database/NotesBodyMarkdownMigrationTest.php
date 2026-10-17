<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotesBodyMarkdownMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_a_nullable_body_markdown_column_to_notes(): void
    {
        $this->assertTrue(Schema::hasColumn('notes', 'body_markdown'));

        $columns = collect(Schema::getColumns('notes'));
        $bodyMarkdown = $columns->firstWhere('name', 'body_markdown');

        $this->assertNotNull($bodyMarkdown);
        $this->assertTrue($bodyMarkdown['nullable']);
        $this->assertTrue($bodyMarkdown['default'] === null || strtoupper((string) $bodyMarkdown['default']) === 'NULL');
    }

    #[Test]
    public function it_stores_raw_markdown_separately_from_rendered_body(): void
    {
        $note = Note::factory()->create([
            'body' => '<p><strong>Rendered</strong></p>',
            'body_markdown' => '**Rendered**',
        ]);

        $stored = $note->fresh();

        $this->assertSame('<p><strong>Rendered</strong></p>', $stored->body);
        $this->assertSame('**Rendered**', $stored->body_markdown);
    }
}
