<?php

namespace Tests\Feature\Tickets;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketTimelineTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function timeline_interleaves_notes_and_changelog_in_order(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'body' => 'First comment', 'created_at' => now()->subMinutes(2)]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'body' => 'Status changed', 'notetype' => 'changelog', 'created_at' => now()->subMinute()]);

        $this->actingAs($user)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSeeInOrder(['First comment', 'Status changed']);
    }
}
