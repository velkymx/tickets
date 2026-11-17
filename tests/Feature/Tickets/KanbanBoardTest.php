<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KanbanBoardTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function board_renders_columns_and_ticket_cards(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['subject' => 'Card subject here']);

        $response = $this->actingAs($user)->get('/tickets/board');

        $response->assertStatus(200);
        $response->assertSee('kanban-board', false);
        $response->assertSee('ticket-column', false);
        $response->assertSee('Card subject here', false);
        $response->assertSee('/tickets/'.$ticket->id, false);
        $response->assertSee('update-alert', false);
    }
}
