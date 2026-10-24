<?php

namespace Tests\Feature\Components;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketTableLayoutTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function estimate_column_replaces_notes_column(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertSee('>Est<', false);
        $response->assertDontSee('>Notes<', false);
    }

    #[Test]
    public function note_count_floats_right_on_subject(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertSee('float-end', false);
    }

    #[Test]
    public function estimate_cell_prefers_points_then_hours_then_dash(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['subject' => 'Points row ticket', 'storypoints' => 5]);
        Ticket::factory()->create(['subject' => 'Hours row ticket', 'storypoints' => 0, 'estimate' => 2.5]);
        Ticket::factory()->create(['subject' => 'Bare row ticket', 'storypoints' => 0, 'estimate' => 0]);

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertSee('5SP', false);
        $response->assertSee('2.5h', false);
        $response->assertDontSee('0SP', false);
    }

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
        $response->assertSee('text-truncate', false);
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
