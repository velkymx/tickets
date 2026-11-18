<?php

namespace Tests\Feature\Api;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddNoteTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\PromoteNoteTool;
use App\Mcp\Tools\ReplyToNoteTool;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\DecisionRequestNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DecisionRequestApiTest extends TestCase
{
    use SeedsDatabase;

    private User $user;

    private string $token = 'test-api-token-that-is-long-enough-for-sha256';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->user = User::factory()->create(['api_token' => hash('sha256', $this->token)]);
    }

    #[Test]
    public function api_list_reports_decision_fields(): void
    {
        $decider = User::factory()->create();
        $flagged = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $plain = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $this->decisionRequest($flagged, $this->user, $decider);

        $data = collect($this->getJson('/api/v1/tickets', $this->apiHeaders())->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($data[$flagged->id]['decision_needed']);
        $this->assertSame($decider->id, $data[$flagged->id]['decision_needed_from']);
        $this->assertFalse($data[$plain->id]['decision_needed']);
        $this->assertNull($data[$plain->id]['decision_needed_from']);
    }

    #[Test]
    public function api_list_filters_to_decisions_from_me_across_owners(): void
    {
        $asker = User::factory()->create();
        $mine = Ticket::factory()->create(['user_id2' => $asker->id]);
        Ticket::factory()->create(['user_id2' => $this->user->id]);
        $this->decisionRequest($mine, $asker, $this->user);

        $ids = collect($this->getJson('/api/v1/tickets?decision=me', $this->apiHeaders())->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    #[Test]
    public function api_list_filters_to_any_open_request_on_my_tickets(): void
    {
        $decider = User::factory()->create();
        $flagged = Ticket::factory()->create(['user_id2' => $this->user->id]);
        Ticket::factory()->create(['user_id2' => $this->user->id]);
        $this->decisionRequest($flagged, $this->user, $decider);

        $ids = collect($this->getJson('/api/v1/tickets?decision=any', $this->apiHeaders())->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$flagged->id], $ids);
    }

    #[Test]
    public function api_promote_creates_a_decision_request(): void
    {
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id, 'body' => 'Which database?']);

        $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
            'assignee' => 'decider',
        ], $this->apiHeaders())->assertOk();

        $this->assertSame($decider->id, (int) $note->fresh()->decider_id);

        $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
        ], $this->apiHeaders())->assertStatus(422);
    }

    #[Test]
    public function api_note_decide_creates_and_notifies(): void
    {
        Notification::fake();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/decide @decider Ship Friday?',
        ], $this->apiHeaders())->assertOk();

        $note = Note::where('ticket_id', $ticket->id)->where('notetype', 'decision_needed')->firstOrFail();
        $this->assertSame($decider->id, $note->decider_id);
        Notification::assertSentToTimes($decider, DecisionRequestNotification::class, 1);

        $this->postJson("/api/v1/tickets/{$ticket->id}/note", [
            'body' => '/decide Ship Friday?',
        ], $this->apiHeaders())->assertStatus(422);
    }

    #[Test]
    public function api_decision_reply_closes_the_request(): void
    {
        $asker = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $request = $this->decisionRequest($ticket, $asker, $this->user);

        $this->postJson("/api/v1/tickets/{$ticket->id}/notes/{$request->id}/reply", [
            'body' => '/decision Ship Friday after the smoke test',
        ], $this->apiHeaders())->assertOk();

        $this->assertTrue($request->fresh()->resolved);
        $this->assertSame('decision', Note::where('parent_id', $request->id)->first()->notetype);
    }

    #[Test]
    public function mcp_list_reports_and_filters_decision_requests(): void
    {
        $asker = User::factory()->create();
        $mine = Ticket::factory()->create(['user_id2' => $asker->id]);
        Ticket::factory()->create(['user_id2' => $this->user->id]);
        $this->decisionRequest($mine, $asker, $this->user);

        $response = TicketsServer::actingAs($this->user)->tool(ListTicketsTool::class, ['decision' => 'me']);

        $response->assertOk();
        $response->assertSee('"decision_needed":true');
        $response->assertSee("\"decision_needed_from\":{$this->user->id}");
        $response->assertSee('"total":1');
    }

    #[Test]
    public function mcp_get_ticket_includes_decision_requests(): void
    {
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $this->decisionRequest($ticket, $this->user, $decider, ['body' => 'Ship Friday?']);

        $response = TicketsServer::actingAs($this->user)->tool(GetTicketTool::class, ['ticket_id' => $ticket->id]);

        $response->assertOk();
        $response->assertSee('decision_requests');
        $response->assertSee('Ship Friday?');
    }

    #[Test]
    public function mcp_promote_creates_a_decision_request(): void
    {
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id, 'body' => 'Which database?']);

        TicketsServer::actingAs($this->user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'type' => 'decision_needed',
            'assignee' => 'decider',
        ])->assertOk();

        $this->assertSame('decision_needed', $note->fresh()->notetype);
        $this->assertSame($decider->id, (int) $note->fresh()->decider_id);
    }

    #[Test]
    public function mcp_add_note_decide_creates_and_notifies(): void
    {
        Notification::fake();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        TicketsServer::actingAs($this->user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/decide @decider Ship Friday?',
        ])->assertOk();

        $note = Note::where('ticket_id', $ticket->id)->where('notetype', 'decision_needed')->firstOrFail();
        $this->assertSame($decider->id, $note->decider_id);
        Notification::assertSentToTimes($decider, DecisionRequestNotification::class, 1);
    }

    #[Test]
    public function mcp_add_note_decide_rejects_a_missing_decider(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);

        TicketsServer::actingAs($this->user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/decide Ship Friday?',
        ])->assertHasErrors(['Decision requests require exactly one @decider']);

        $this->assertSame(0, Note::where('ticket_id', $ticket->id)->count());
    }

    #[Test]
    public function mcp_add_note_decision_reply_closes_the_request(): void
    {
        $asker = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $this->user->id]);
        $request = $this->decisionRequest($ticket, $asker, $this->user);

        TicketsServer::actingAs($this->user)->tool(ReplyToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $request->id,
            'body' => '/decision Ship Friday after the smoke test',
        ])->assertOk();

        $this->assertTrue($request->fresh()->resolved);
    }

    private function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
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
