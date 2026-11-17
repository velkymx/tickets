<?php

namespace Tests\Feature;

use App\Models\Note;
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
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/tickets/{$ticket->id}/pulse");

        $response->assertOk()
            ->assertJsonStructure([
                'id',
                'status',
                'is_blocked',
                'owner_label',
                'viewers',
            ])
            ->assertJsonPath('id', $ticket->id)
            ->assertJsonPath('status', $ticket->status->name);
    }

    #[Test]
    public function it_updates_presence_when_fetching_pulse()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $this->actingAs($user)
            ->getJson("/tickets/{$ticket->id}/pulse");

        $viewers = (new PresenceService)->getViewers($ticket->id);
        $this->assertCount(1, $viewers);
        $this->assertEquals($user->id, $viewers[0]['user_id']);
    }

    #[Test]
    public function pulse_endpoint_lists_newly_created_notes_as_resolvable()
    {
        $author = User::factory()->create();
        $involved = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $involved->id]);

        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'notetype' => 'blocker',
        ]);

        $involvedResponse = $this->actingAs($involved)->getJson("/tickets/{$ticket->id}/pulse");
        $involvedResponse->assertOk();
        $this->assertContains($note->id, $involvedResponse->json('resolvable_note_ids'));

        $strangerResponse = $this->actingAs($stranger)->getJson("/tickets/{$ticket->id}/pulse");
        $strangerResponse->assertOk();
        $this->assertNotContains($note->id, $strangerResponse->json('resolvable_note_ids'));
    }
}
