<?php

namespace Tests\Feature\Tickets;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\DecisionRequestNotification;
use App\Notifications\MentionNotification;
use App\Services\TicketPulseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DecisionRequestTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function decide_command_creates_a_decision_request_for_the_mentioned_user(): void
    {
        $author = User::factory()->create();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);

        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide @decider Ship with SQLite or MySQL?',
        ])->assertRedirect();

        $note = Note::where('ticket_id', $ticket->id)->where('notetype', 'decision_needed')->first();

        $this->assertNotNull($note);
        $this->assertSame($decider->id, (int) $note->decider_id);
        $this->assertFalse($note->resolved);
        $this->assertStringContainsString('Ship with SQLite or MySQL?', $note->body);
    }

    #[Test]
    public function decide_command_requires_exactly_one_mention(): void
    {
        $author = User::factory()->create();
        User::factory()->create(['name' => 'alpha']);
        User::factory()->create(['name' => 'beta']);
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);

        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide Which database?',
        ])->assertSessionHasErrors('note');
        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide @alpha @beta Which database?',
        ])->assertSessionHasErrors('note');

        $this->assertSame(0, Note::where('ticket_id', $ticket->id)->where('notetype', 'decision_needed')->count());
        // Guard failures are errors, not changelog entries.
        $this->assertSame(0, Note::where('ticket_id', $ticket->id)->where('notetype', 'changelog')->count());
    }

    #[Test]
    public function decide_command_rejects_an_unknown_user(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);

        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide @nobody Which database?',
        ]);

        $this->assertSame(0, Note::where('ticket_id', $ticket->id)->where('notetype', 'decision_needed')->count());
    }

    #[Test]
    public function replying_with_a_decision_closes_the_request(): void
    {
        $author = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        $request = $this->decisionRequest($ticket, $author, $decider);

        $this->actingAs($decider)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $request->id,
            'body' => '/decision Go with MySQL for production installs',
        ])->assertOk();

        $request->refresh();

        $this->assertTrue($request->resolved);
        $this->assertSame($decider->id, (int) $request->resolved_by);
        $this->assertSame('decision', Note::where('parent_id', $request->id)->first()->notetype);
    }

    #[Test]
    public function a_plain_reply_keeps_the_request_open(): void
    {
        $author = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        $request = $this->decisionRequest($ticket, $author, $decider);

        $this->actingAs($decider)->postJson('/notes/reply', [
            'ticket_id' => $ticket->id,
            'parent_id' => $request->id,
            'body' => 'Thinking about it',
        ])->assertOk();

        $this->assertFalse($request->fresh()->resolved);
    }

    #[Test]
    public function the_decider_can_resolve_the_request(): void
    {
        $owner = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $owner->id]);
        $request = $this->decisionRequest($ticket, $owner, $decider);

        $this->actingAs($decider)->postJson("/notes/{$request->id}/resolve", [
            'resolution_message' => 'Decided offline: MySQL',
        ])->assertOk();

        $this->assertTrue($request->fresh()->resolved);
    }

    #[Test]
    public function a_message_can_be_promoted_to_a_decision_request(): void
    {
        $owner = User::factory()->create();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $owner->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'body' => 'Which database should we ship?',
        ]);

        $this->actingAs($owner)->post("/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
            'assignee' => 'decider',
        ])->assertRedirect();

        $note->refresh();

        $this->assertSame('decision_needed', $note->notetype);
        $this->assertSame($decider->id, (int) $note->decider_id);
    }

    #[Test]
    public function promoting_to_a_decision_request_requires_a_known_decider(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $owner->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'body' => 'Which database should we ship?',
        ]);

        $this->actingAs($owner)->postJson("/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
        ])->assertStatus(422);

        $this->actingAs($owner)->postJson("/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
            'assignee' => 'nobody',
        ])->assertStatus(422);

        $this->assertSame('message', $note->fresh()->notetype);
    }

    #[Test]
    public function pulse_lists_open_decision_requests_only(): void
    {
        $author = User::factory()->create(['name' => 'asker']);
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        $open = $this->decisionRequest($ticket, $author, $decider);
        $this->decisionRequest($ticket, $author, $decider, ['resolved' => true]);
        $thread = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id]);
        Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id, 'parent_id' => $thread->id]);

        $pulse = app(TicketPulseService::class)->getPulse($ticket->fresh());

        $this->assertCount(1, $pulse->decision_requests);
        $this->assertSame($open->id, $pulse->decision_requests[0]['id']);
        $this->assertSame('decider', $pulse->decision_requests[0]['decider']);
        $this->assertSame($decider->id, $pulse->decision_requests[0]['decider_id']);
        $this->assertSame('asker', $pulse->decision_requests[0]['requester']);
        $this->assertContains($open->id, array_column($pulse->open_threads, 'id'));
    }

    #[Test]
    public function decide_command_notifies_the_decider_once(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);

        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide @decider Ship with SQLite or MySQL?',
        ]);

        Notification::assertSentToTimes($decider, DecisionRequestNotification::class, 1);
        Notification::assertNotSentTo($decider, MentionNotification::class);
    }

    #[Test]
    public function asking_yourself_sends_no_notification(): void
    {
        Notification::fake();
        $author = User::factory()->create(['name' => 'author']);
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);

        $this->actingAs($author)->post('/notes', [
            'ticket_id' => $ticket->id,
            'note' => '/decide @author Ship with SQLite or MySQL?',
        ]);

        Notification::assertNotSentTo($author, DecisionRequestNotification::class);
    }

    #[Test]
    public function promoting_to_a_decision_request_notifies_the_decider(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $decider = User::factory()->create(['name' => 'decider']);
        $ticket = Ticket::factory()->create(['user_id2' => $owner->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'body' => 'Which database should we ship?',
        ]);

        $this->actingAs($owner)->post("/notes/{$note->id}/promote", [
            'type' => 'decision_needed',
            'assignee' => 'decider',
        ]);

        Notification::assertSentToTimes($decider, DecisionRequestNotification::class, 1);
    }

    #[Test]
    public function latest_decision_carries_the_question_it_answers(): void
    {
        $author = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        $request = $this->decisionRequest($ticket, $author, $decider, ['body' => "<p>Ship on Friday?</p>\n<p>More context</p>", 'resolved' => true]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $decider->id,
            'parent_id' => $request->id,
            'notetype' => 'decision',
            'body' => 'yes!',
        ]);

        $pulse = app(TicketPulseService::class)->getPulse($ticket->fresh());

        $this->assertSame('yes!', $pulse->latest_decision['body']);
        $this->assertSame('Ship on Friday?', $pulse->latest_decision['question']);
    }

    #[Test]
    public function a_top_level_decision_has_no_question(): void
    {
        $author = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'notetype' => 'decision',
            'body' => 'We will use MySQL in production',
        ]);

        $pulse = app(TicketPulseService::class)->getPulse($ticket->fresh());

        $this->assertNull($pulse->latest_decision['question']);
    }

    #[Test]
    public function sidebar_payload_carries_the_question_and_latest_decision(): void
    {
        $author = User::factory()->create();
        $decider = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $author->id]);
        $request = $this->decisionRequest($ticket, $author, $decider, ['body' => '<p>Ship on Friday?</p>']);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $decider->id,
            'parent_id' => $request->id,
            'notetype' => 'decision',
            'body' => 'Yes, ship it',
        ]);

        // The pulse panel renders client side from JSON embedded by @js, so the
        // HTTP test pins the payload it is handed, not the Alpine template.
        $this->actingAs($author)->get("/tickets/{$ticket->id}")
            ->assertOk()
            ->assertSee('Ship on Friday?')
            ->assertSee('Yes, ship it');
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
