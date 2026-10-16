<?php

namespace Tests\Feature\Tickets;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PulseDecisionBannerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function show_calls_out_open_threads_first(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);
        $thread = Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);
        Note::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Needs your decision', $html);
        $this->assertLessThan(
            strpos($html, 'id="pulse-threads"'),
            strpos($html, 'Needs your decision')
        );
    }

    #[Test]
    public function banner_template_ships_even_without_open_threads(): void
    {
        // refresh() replaces pulse client-side, so the template must be
        // in the DOM on every load for late-arriving threads to reveal.
        // Asserts markup presence, not visibility: x-if gates display.
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertOk();
        $response->assertSee('Needs your decision', false);
    }
}
