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

    #[Test]
    public function ticket_list_marks_rows_with_open_requests(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $flagged = Ticket::factory()->create(['user_id2' => $asker->id]);
        Ticket::factory()->create(['user_id2' => $asker->id]);
        $this->decisionRequest($flagged, $asker, $decider);

        $response = $this->actingAs($asker)->get('/tickets');

        $response->assertOk();
        $this->assertSame(2, substr_count($response->getContent(), 'data-decision-needed'));
    }

    #[Test]
    public function ticket_list_filters_to_decisions_from_me(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $other = User::factory()->create();
        $mine = Ticket::factory()->create(['user_id2' => $asker->id, 'subject' => 'Waiting on me']);
        $theirs = Ticket::factory()->create(['user_id2' => $asker->id, 'subject' => 'Waiting on other']);
        $plain = Ticket::factory()->create(['user_id2' => $asker->id, 'subject' => 'Nothing pending']);
        $this->decisionRequest($mine, $asker, $decider);
        $this->decisionRequest($theirs, $asker, $other);

        $this->actingAs($decider)->get('/tickets?q=decision:me')
            ->assertOk()
            ->assertSee('Waiting on me')
            ->assertDontSee('Waiting on other')
            ->assertDontSee('Nothing pending');

        $this->actingAs($decider)->get('/tickets?q=decision:any')
            ->assertOk()
            ->assertSee('Waiting on me')
            ->assertSee('Waiting on other')
            ->assertDontSee('Nothing pending');
    }

    #[Test]
    public function ticket_list_header_offers_needs_my_decision_with_count(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id]);
        $this->decisionRequest($ticket, $asker, $decider);

        $this->actingAs($decider)->get('/tickets')
            ->assertOk()
            ->assertSeeInOrder(['btn btn-sm btn-outline-danger', 'Needs my decision', '>1<', 'Create Ticket'], false);

        $this->actingAs($decider)->get('/tickets?q=decision:me')
            ->assertOk()
            ->assertSee('btn btn-sm btn-danger', false)
            ->assertSee('aria-current="true"', false);
    }

    #[Test]
    public function saved_views_row_stays_hidden_without_views_or_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/tickets')
            ->assertOk()
            ->assertDontSee('Save view as', false);
    }

    #[Test]
    public function navbar_counts_decisions_waiting_on_me_on_every_page(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $first = Ticket::factory()->create(['user_id2' => $asker->id]);
        $second = Ticket::factory()->create(['user_id2' => $asker->id]);
        $this->decisionRequest($first, $asker, $decider);
        $this->decisionRequest($first, $asker, $decider);
        $this->decisionRequest($second, $asker, $decider);
        $this->decisionRequest($second, $asker, $asker);

        $response = $this->actingAs($decider)->get('/projects');

        $response->assertOk();
        $response->assertSee('data-decision-count="3"', false);
        $response->assertSee('/tickets?q=decision%3Ame', false);
    }

    #[Test]
    public function navbar_hides_the_badge_with_nothing_waiting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/projects')
            ->assertOk()
            ->assertDontSee('data-decision-count', false);
    }

    #[Test]
    public function ticket_page_leads_with_a_banner_for_each_open_request(): void
    {
        $asker = User::factory()->create(['name' => 'asker']);
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id, 'subject' => 'Pick a database']);
        $request = $this->decisionRequest($ticket, $asker, $decider, ['body' => 'SQLite or MySQL for prod?']);
        $this->decisionRequest($ticket, $asker, $decider, ['body' => 'Already settled question', 'resolved' => true]);

        $response = $this->actingAs($decider)->get("/tickets/{$ticket->id}");

        $response->assertOk();
        $response->assertSeeInOrder(['data-decision-banner', 'Decision needed from decider', 'SQLite or MySQL for prod?', 'Asked by asker', 'Record decision', 'Pick a database'], false);
        $response->assertSee("data-record-decision=\"{$request->id}\"", false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-decision-banner'));
    }

    #[Test]
    public function ticket_page_banner_escalates_with_wait_time(): void
    {
        $asker = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $asker->id]);
        $request = $this->decisionRequest($ticket, $asker, $decider);
        $request->forceFill(['created_at' => now()->subHours(30)])->save();

        $this->actingAs($decider)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('alert alert-warning', false)
            ->assertSee('data-decision-urgency="warning"', false);
    }

    #[Test]
    public function ticket_page_has_no_banner_without_open_requests(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $this->actingAs($user)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertDontSee('data-decision-banner', false);
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
