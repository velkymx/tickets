<?php

namespace Tests\Feature\Components;

use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketTableLayoutTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function list_renders_stacked_cards_for_small_screens(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['subject' => 'Card layout ticket']);

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertOk();
        $response->assertSee('ticket-card', false);
        $response->assertSee('Card layout ticket');
    }

    #[Test]
    public function table_hides_low_value_columns_on_small_screens(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertOk();
        $response->assertSee('d-none d-lg-table-cell', false);
    }

    #[Test]
    public function table_truncates_long_text_cells(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertOk();
        $response->assertSee('ticket-cell-truncate', false);
    }

    #[Test]
    public function table_keeps_absolute_timestamps_for_scanning(): void
    {
        // The table shows absolute time (dense, sortable by eye); the
        // relative label lives only on the small-screen cards.
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertOk();
        $response->assertSee($ticket->updated_at->format('M jS, Y g:ia'), false);
    }

    #[Test]
    public function panes_are_marked_for_bulk_select(): void
    {
        // bulk-select.js matches checkboxes to panes by data-pane, not by
        // layout probing, so both panes must carry the marker.
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertOk();
        $response->assertSee('data-pane="table"', false);
        $response->assertSee('data-pane="cards"', false);
    }
}
