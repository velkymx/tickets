<?php

namespace Tests\Feature\Tickets;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DecisionRequestSurfacesTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function home_lists_decisions_waiting_on_me(): void
    {
        $asker = User::factory()->create(['name' => 'asker']);
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id, 'subject' => 'Database choice']);
        $this->decisionRequest($ticket, $asker, $decider, ['body' => 'SQLite or MySQL for prod?']);

        $response = $this->actingAs($decider)->get('/home');

        $response->assertOk();
        $response->assertSeeInOrder(['Needs My Decision', 'Database choice', 'SQLite or MySQL for prod?', 'asker', 'Watching'], false);
    }

    #[Test]
    public function home_hides_the_box_without_open_requests(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id]);
        $this->decisionRequest($ticket, $asker, $decider, ['resolved' => true]);
        $this->decisionRequest($ticket, $asker, $other);

        $this->actingAs($decider)->get('/home')->assertOk()->assertDontSee('Needs My Decision');
    }

    #[Test]
    public function home_skips_requests_on_closed_tickets(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $closed = Status::factory()->closed()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id, 'status_id' => $closed->id]);
        $this->decisionRequest($ticket, $asker, $decider);

        $this->actingAs($decider)->get('/home')->assertOk()->assertDontSee('Needs My Decision');
    }

    #[Test]
    public function home_escalates_old_requests(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id]);
        $old = $this->decisionRequest($ticket, $asker, $decider);
        $old->forceFill(['created_at' => now()->subHours(80)])->save();

        $response = $this->actingAs($decider)->get('/home');

        $response->assertOk();
        $response->assertSee('data-decision-urgency="danger"', false);
    }

    private function decisionRequest(Ticket $ticket, User $author, User $decider, array $attributes = []): Note
    {
        return Note::factory()->create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'notetype' => 'decision_needed',
            'decider_id' => $decider->id,
            'body' => 'Which database should we ship?',
            'resolved' => false,
        ], $attributes));
    }
}
