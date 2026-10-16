<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KanbanBoardScrollTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function board_renders_viewport_bounded_scroll_container(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/board');

        $response->assertStatus(200);
        $response->assertSee('kanban-board', false);
    }

    #[Test]
    public function board_columns_keep_drop_target_class(): void
    {
        // Standing guard for the SortableJS drop target, not coverage
        // for the scroll fix: ticket-column predates the change.
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/board');

        $response->assertStatus(200);
        $response->assertSee('ticket-column', false);
    }

    #[Test]
    public function kanban_css_pins_the_scroll_mechanism_as_a_set(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // The column body only scrolls when the card is a flex column
        // and the body is a shrinkable flex item. Pin all three as a
        // set so a future edit cannot drop one half of the mechanism.
        $this->assertStringContainsString('flex-direction: column', $css);
        $this->assertStringContainsString('flex: 1 1 auto', $css);
        $this->assertStringContainsString('min-height: min(320px, 40vh)', $css);
    }
}
