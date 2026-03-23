<?php

namespace Tests\Unit;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlashCommandService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SlashCommandServiceTest extends TestCase
{
    use SeedsDatabase;

    private SlashCommandService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlashCommandService;
    }

    #[Test]
    public function it_can_change_status_via_slash_command()
    {
        $ticket = Ticket::factory()->create();
        $status = Status::factory()->create(['name' => 'Testing']);

        $result = $this->service->handle($ticket, '/status Testing');

        $this->assertEquals($status->id, $ticket->fresh()->status_id);
        $this->assertSame([
            ['action' => 'status_changed', 'to' => 'Testing'],
        ], $result['actions']);
    }

    #[Test]
    public function it_rejects_status_changes_when_the_ticket_has_an_active_blocker()
    {
        $ticket = Ticket::factory()->create();
        $author = User::factory()->create();
        $status = Status::factory()->create(['name' => 'Testing']);

        Note::create([
            'body' => 'Blocked on API rollout',
            'user_id' => $author->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $result = $this->service->handle($ticket, '/status Testing');

        $this->assertNotEquals($status->id, $ticket->fresh()->status_id);
        $this->assertContains('Resolve blocker before changing status', $result['changes']);
    }

    #[Test]
    public function it_can_assign_user_via_slash_command()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['name' => 'JohnDoe']);

        $result = $this->service->handle($ticket, '/assign @[JohnDoe]');

        $this->assertEquals($user->id, $ticket->fresh()->user_id2);
        $this->assertSame([
            ['action' => 'assigned', 'to' => 'JohnDoe'],
        ], $result['actions']);
    }

    #[Test]
    public function it_can_close_ticket_via_slash_command()
    {
        $ticket = Ticket::factory()->create(['closed_at' => null]);

        $result = $this->service->handle($ticket, '/close');

        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertSame([
            ['action' => 'closed'],
        ], $result['actions']);
    }

    #[Test]
    public function it_rejects_close_when_the_ticket_has_an_active_blocker()
    {
        $ticket = Ticket::factory()->create(['closed_at' => null]);
        $author = User::factory()->create();

        Note::create([
            'body' => 'Blocked on API rollout',
            'user_id' => $author->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $result = $this->service->handle($ticket, '/close');

        $this->assertNull($ticket->fresh()->closed_at);
        $this->assertContains('Resolve blocker before changing status', $result['changes']);
    }

    #[Test]
    public function it_can_log_hours_via_slash_command()
    {
        $ticket = Ticket::factory()->create();

        $results = $this->service->handle($ticket, '/hours 2.5');

        $this->assertEquals(2.5, $results['hours']);
        $this->assertSame([
            ['action' => 'hours_logged', 'value' => 2.5],
        ], $results['actions']);
    }

    #[Test]
    public function it_can_set_estimate_via_slash_command()
    {
        $ticket = Ticket::factory()->create(['estimate' => 0]);

        $result = $this->service->handle($ticket, '/estimate 5');

        $this->assertEquals(5, $ticket->fresh()->estimate);
        $this->assertSame([
            ['action' => 'estimate_changed', 'to' => 5],
        ], $result['actions']);
    }

    #[Test]
    public function it_can_change_priority_and_milestone_via_slash_commands()
    {
        $ticket = Ticket::factory()->create();
        $importance = Importance::factory()->create(['name' => 'Critical']);
        $milestone = Milestone::factory()->create(['name' => 'Beta Release']);

        $result = $this->service->handle($ticket, "/priority Critical\n/milestone Beta Release");

        $this->assertEquals($importance->id, $ticket->fresh()->importance_id);
        $this->assertEquals($milestone->id, $ticket->fresh()->milestone_id);
        $this->assertSame([
            ['action' => 'importance_changed', 'to' => 'Critical'],
            ['action' => 'milestone_changed', 'to' => 'Beta Release'],
        ], $result['actions']);
    }

    #[Test]
    public function it_supports_multi_command_submissions_and_collects_body_text()
    {
        $ticket = Ticket::factory()->create();
        $assignee = User::factory()->create(['name' => 'john']);
        $status = Status::factory()->create(['name' => 'Testing']);

        $result = $this->service->handle($ticket, "/assign @[john]\n/status Testing\n/action Verify fix on staging @[john]\nFound the root cause in the payment handler.");

        $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
        $this->assertEquals($status->id, $ticket->fresh()->status_id);
        $this->assertSame('action', $result['note_type']);
        $this->assertStringContainsString('Verify fix on staging @[john]', $result['body']);
        $this->assertStringContainsString('Found the root cause in the payment handler.', $result['body']);
        $this->assertSame([
            ['action' => 'assigned', 'to' => 'john'],
            ['action' => 'status_changed', 'to' => 'Testing'],
            ['action' => 'signal_set', 'to' => 'action'],
        ], $result['actions']);
    }

    #[Test]
    public function it_requires_exactly_one_assignee_for_action_commands()
    {
        $ticket = Ticket::factory()->create();

        $missing = $this->service->handle($ticket, '/action Verify fix');
        $multiple = $this->service->handle($ticket, '/action Verify fix @[john] @[sarah]');

        $this->assertContains('Actions require exactly one @assignee', $missing['changes']);
        $this->assertContains('Actions require exactly one @assignee', $multiple['changes']);
    }

    #[Test]
    public function it_limits_open_action_commands_to_three_unresolved_notes()
    {
        $ticket = Ticket::factory()->create();
        $author = User::factory()->create();
        User::factory()->create(['name' => 'john']);

        Note::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'notetype' => 'action',
            'resolved' => false,
        ]);

        $result = $this->service->handle($ticket, '/action Verify fix @[john]');

        $this->assertContains('Too many open actions. Resolve or overwrite an existing action before creating a new one', $result['changes']);
    }

    #[Test]
    public function it_auto_assigns_unassigned_tickets_to_the_action_assignee()
    {
        $ticket = Ticket::factory()->create(['user_id2' => 999999]);
        $assignee = User::factory()->create(['name' => 'sarah']);

        $result = $this->service->handle($ticket, '/action Verify fix @[sarah]');

        $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
        $this->assertContains(['action' => 'assigned', 'to' => 'sarah'], $result['actions']);
    }

    #[Test]
    public function it_can_mark_a_note_as_pinned_via_slash_command()
    {
        $ticket = Ticket::factory()->create();

        $result = $this->service->handle($ticket, '/pin');

        $this->assertTrue($result['note_attributes']['pinned']);
        $this->assertContains(['action' => 'pinned'], $result['actions']);
    }

    #[Test]
    public function it_flags_unknown_commands_and_treats_them_as_text()
    {
        $ticket = Ticket::factory()->create();

        $result = $this->service->handle($ticket, "/statsu testing\nMessage body");

        $this->assertContains('Unknown command: /statsu — will be treated as text', $result['warnings']);
        $this->assertStringContainsString('/statsu testing', $result['body']);
        $this->assertStringContainsString('Message body', $result['body']);
    }

    #[Test]
    public function it_can_assign_user_via_bracket_mention(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['name' => 'John Smith']);

        $result = $this->service->handle($ticket, '/assign @[John Smith (Developer)]');

        $this->assertEquals($user->id, $ticket->fresh()->user_id2);
        $this->assertSame([
            ['action' => 'assigned', 'to' => 'John Smith'],
        ], $result['actions']);
    }

    #[Test]
    public function it_extracts_bracket_mentions_in_action_commands(): void
    {
        $ticket = Ticket::factory()->create(['user_id2' => 999999]);
        $assignee = User::factory()->create(['name' => 'Sarah Lee']);

        $result = $this->service->handle($ticket, '/action Verify fix @[Sarah Lee (QA)]');

        $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
        $this->assertSame('action', $result['note_type']);
    }

    #[Test]
    public function it_falls_back_to_bare_name_for_assign(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['name' => 'John Smith']);

        $result = $this->service->handle($ticket, '/assign John Smith');

        $this->assertEquals($user->id, $ticket->fresh()->user_id2);
    }

    #[Test]
    public function it_extracts_signal_type_from_command()
    {
        $ticket = Ticket::factory()->create();

        $type = $this->service->getSignalType('/decision We use Redis');
        $this->assertEquals('decision', $type);

        $type = $this->service->getSignalType('/blocker Need API keys');
        $this->assertEquals('blocker', $type);
    }
}
