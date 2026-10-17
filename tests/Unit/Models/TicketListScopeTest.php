<?php

namespace Tests\Unit\Models;

use App\Models\Note;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketListScopeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function list_scope_loads_relations_and_visible_notes_only(): void
    {
        $ticket = Ticket::factory()->create();
        $visible = Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'message', 'hide' => false]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'message', 'hide' => true]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'notetype' => 'changelog', 'hide' => false]);

        $loaded = Ticket::withListRelations()->find($ticket->id);

        foreach (['status', 'type', 'importance', 'project', 'assignee'] as $relation) {
            $this->assertTrue($loaded->relationLoaded($relation), "{$relation} should be eager loaded");
        }
        $this->assertTrue($loaded->relationLoaded('notes'));
        $this->assertEquals([$visible->id], $loaded->notes->pluck('id')->all());
    }

    #[Test]
    public function list_sortable_is_shared(): void
    {
        $this->assertSame(
            ['subject', 'importance_id', 'status_id', 'project_id', 'created_at', 'updated_at'],
            Ticket::LIST_SORTABLE
        );
    }
}
