<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\PresenceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketPulseControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_returns_pulse_data()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/tickets/{$ticket->id}/pulse");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'status',
                'is_blocked',
                'owner_label',
                'viewers',
            ]);
    }

    #[Test]
    public function it_updates_presence_when_fetching_pulse()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)
            ->getJson("/tickets/{$ticket->id}/pulse");

        $viewers = (new PresenceService)->getViewers($ticket->id);
        $this->assertCount(1, $viewers);
        $this->assertEquals($user->id, $viewers[0]['user_id']);
    }
}
