<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddNoteTool;
use App\Mcp\Tools\CreateMilestoneTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\EditNoteTool;
use App\Mcp\Tools\GetLookupsTool;
use App\Mcp\Tools\GetMilestoneTool;
use App\Mcp\Tools\GetPulseTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListMilestonesTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\ModerateNoteTool;
use App\Mcp\Tools\PromoteNoteTool;
use App\Mcp\Tools\ReactToNoteTool;
use App\Mcp\Tools\ReplyToNoteTool;
use App\Mcp\Tools\ResolveNoteTool;
use App\Mcp\Tools\SearchTicketsTool;
use App\Mcp\Tools\UpdateMilestoneTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Mcp\Tools\WatchTicketTool;
use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use App\Services\MarkdownService;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Server\Contracts\Transport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketsServerTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function lookups_tool_returns_all_lookup_tables(): void
    {
        $user = User::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(GetLookupsTool::class, []);

        $response->assertOk();
        $response->assertSee('milestones');
    }

    #[Test]
    #[DataProvider('unauthenticatedToolCalls')]
    public function tools_reject_unauthenticated_calls(string $tool, array $args): void
    {
        TicketsServer::tool($tool, $args)->assertHasErrors(['Unauthenticated']);
    }

    public static function unauthenticatedToolCalls(): array
    {
        return [
            'lookups' => [GetLookupsTool::class, []],
            'list milestones' => [ListMilestonesTool::class, []],
            'search tickets' => [SearchTicketsTool::class, ['query' => 'login']],
        ];
    }

    #[Test]
    public function list_tickets_tool_returns_assigned_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(ListTicketsTool::class, []);

        $response->assertOk();
        $response->assertSee($ticket->subject);
    }

    #[Test]
    public function get_ticket_tool_returns_detail_and_pulse(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(GetTicketTool::class, [
            'ticket_id' => $ticket->id,
        ]);

        $response->assertOk();
        $response->assertSee($ticket->subject);
        $response->assertSee('pulse');
    }

    #[Test]
    public function create_ticket_tool_creates_ticket(): void
    {
        $user = User::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateTicketTool::class, [
            'subject' => 'MCP created ticket',
            'type_id' => Type::factory()->create()->id,
            'importance_id' => Importance::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'milestone_id' => Milestone::factory()->create()->id,
        ]);

        $response->assertOk();
        $response->assertSee('Ticket created.');
        $this->assertDatabaseHas('tickets', [
            'subject' => 'MCP created ticket',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function create_ticket_tool_creates_unassigned_ticket(): void
    {
        $user = User::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateTicketTool::class, [
            'subject' => 'Unassigned via MCP',
            'type_id' => Type::factory()->create()->id,
            'importance_id' => Importance::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'milestone_id' => Milestone::factory()->create()->id,
            'assignee_id' => null,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Unassigned via MCP',
            'user_id' => $user->id,
            'user_id2' => null,
        ]);
    }

    #[Test]
    public function create_ticket_tool_assigns_to_another_user(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateTicketTool::class, [
            'subject' => 'Assigned via MCP',
            'type_id' => Type::factory()->create()->id,
            'importance_id' => Importance::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'milestone_id' => Milestone::factory()->create()->id,
            'assignee_id' => $assignee->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Assigned via MCP',
            'user_id' => $user->id,
            'user_id2' => $assignee->id,
        ]);
    }

    #[Test]
    public function add_note_tool_adds_note_with_hours(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => 'Investigating via MCP',
            'hours' => 1.5,
        ]);

        $response->assertOk();
        $response->assertSee('Note added.');
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function add_note_tool_supports_slash_commands(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/close Fixed via MCP',
        ]);

        $response->assertOk();
        $this->assertTrue(Status::isClosed($ticket->fresh()->status_id));
    }

    #[Test]
    public function add_note_tool_accepts_bracket_mention_with_space_for_actions(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Sarah Connor']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => null,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/action @[Sarah Connor] verify fix on staging',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
        ]);
        $this->assertSame($assignee->id, $ticket->fresh()->user_id2);
    }

    #[Test]
    public function add_note_tool_rejects_email_address_as_action_assignee(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/action Contact support@example.com about the outage',
        ]);

        $response->assertHasErrors();
        $this->assertDatabaseMissing('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
        ]);
    }

    #[Test]
    public function get_pulse_tool_returns_execution_state(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(GetPulseTool::class, [
            'ticket_id' => $ticket->id,
        ]);

        $response->assertOk();
        $response->assertSee('execution_state');
    }

    #[Test]
    public function server_acknowledges_the_initialized_lifecycle_request(): void
    {
        $transport = new class implements Transport
        {
            /** @var array<int, string> */
            public array $sent = [];

            public function onReceive(\Closure $handler): void {}

            public function run(): void {}

            public function send(string $message): void
            {
                $this->sent[] = $message;
            }

            public function stream(\Closure $stream): void
            {
                foreach ($stream() as $message) {
                    $this->sent[] = $message;
                }
            }
        };

        $server = new TicketsServer($transport);
        $server->start();
        $server->handle((string) json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'notifications/initialized',
        ]));

        $this->assertCount(1, $transport->sent);

        $reply = json_decode($transport->sent[0], true);

        $this->assertSame(1, $reply['id']);
        $this->assertArrayNotHasKey('error', $reply);
    }

    #[Test]
    public function write_to_foreign_ticket_returns_clean_not_found(): void
    {
        $alan = User::factory()->create(['name' => 'Alan']);
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $other->id,
            'user_id2' => $other->id,
        ]);

        $response = TicketsServer::actingAs($alan)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'subject' => 'Hijack attempt',
        ]);

        $response->assertHasErrors(['Ticket not found.']);
        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
            'subject' => 'Hijack attempt',
        ]);
    }

    #[Test]
    public function get_ticket_tool_includes_note_metadata(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(GetTicketTool::class, [
            'ticket_id' => $ticket->id,
        ]);

        $response->assertOk();
        $response->assertSee('reactions');
        $response->assertSee('edited_at');
        $response->assertSee('resolution_message');
    }

    #[Test]
    public function add_note_tool_skips_empty_command_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => '/estimate 8',
        ]);

        $response->assertOk();
        $response->assertSee('no note stored');
        $this->assertDatabaseMissing('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);
        // The field change still lands in the changelog for the audit trail.
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function add_note_tool_rolls_back_status_change_when_slash_guard_fails(): void
    {
        $user = User::factory()->create();
        [$startStatus, $targetStatus] = Status::orderBy('id')->take(2)->get();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $startStatus->id,
        ]);
        // Active blocker makes /close fail its guard.
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'status_id' => $targetStatus->id,
            'body' => '/close',
        ]);

        $response->assertHasErrors(['Resolve blocker']);
        // The status_id write must have rolled back with the failed guard.
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status_id' => $startStatus->id,
        ]);
        $this->assertDatabaseMissing('notes', [
            'ticket_id' => $ticket->id,
            'body_markdown' => '/close',
        ]);
        // Guard failures are errors, not changelog entries.
        $this->assertDatabaseMissing('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function resolve_tool_rejects_already_resolved_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
            'resolved' => true,
        ]);

        $response = TicketsServer::actingAs($user)->tool(ResolveNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'resolution_message' => 'Again',
        ]);

        $response->assertHasErrors(['already resolved']);
    }

    #[Test]
    public function update_ticket_tool_updates_subject_and_status(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'subject' => 'Renamed via MCP',
        ]);

        $response->assertOk();
        $response->assertSee('Renamed via MCP');
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Renamed via MCP',
        ]);
    }

    #[Test]
    public function update_ticket_tool_reassigns_ticket(): void
    {
        $user = User::factory()->create();
        $newAssignee = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'assignee_id' => $newAssignee->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'user_id2' => $newAssignee->id,
        ]);
    }

    #[Test]
    public function update_ticket_tool_updates_planning_fields(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $type = Type::factory()->create();
        $importance = Importance::factory()->create();
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'type_id' => $type->id,
            'importance_id' => $importance->id,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'due_at' => '2026-12-01',
            'estimate' => 8,
            'storypoints' => 5,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'type_id' => $type->id,
            'importance_id' => $importance->id,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'storypoints' => 5,
        ]);
        $this->assertEquals('8.00', $ticket->fresh()->estimate);
    }

    #[Test]
    public function moderate_note_tool_pins_and_hides(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        TicketsServer::actingAs($user)->tool(ModerateNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'action' => 'pin',
        ])->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'pinned' => true]);

        TicketsServer::actingAs($user)->tool(ModerateNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'action' => 'hide',
        ])->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'hide' => true]);
    }

    #[Test]
    public function moderate_note_tool_rejects_a_foreign_ticket(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $other->id, 'user_id2' => $other->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $other->id]);

        $response = TicketsServer::actingAs($user)->tool(ModerateNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'action' => 'pin',
        ]);

        $response->assertHasErrors(['Ticket not found.']);
    }

    #[Test]
    public function promote_note_tool_promotes_a_message_to_a_decision(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'We will ship the new billing flow next sprint.',
        ]);

        $response = TicketsServer::actingAs($user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'decision',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'notetype' => 'decision']);
    }

    #[Test]
    public function promote_note_tool_requires_assignee_for_actions(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'alan']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'Someone should look at this.',
        ]);

        TicketsServer::actingAs($user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'action',
        ])->assertHasErrors(['Actions require exactly one @assignee.']);

        TicketsServer::actingAs($user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'action', 'assignee' => 'alan',
        ])->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'notetype' => 'action']);
    }

    #[Test]
    public function promote_note_tool_uses_stored_mention_for_action(): void
    {
        $user = User::factory()->create();
        $jane = User::factory()->create(['name' => 'Jane Doe']);
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $markdown = 'Fix the @param docblock, @[Jane Doe]';
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => app(MarkdownService::class)->parse($markdown),
            'body_markdown' => $markdown,
        ]);

        TicketsServer::actingAs($user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'action',
        ])->assertOk();

        $this->assertDatabaseHas('notes', ['id' => $note->id, 'notetype' => 'action']);
        $this->assertDatabaseHas('mentions', ['note_id' => $note->id, 'user_id' => $jane->id]);
    }

    #[Test]
    public function promote_note_tool_rejects_non_message_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id, 'user_id' => $user->id, 'notetype' => 'decision',
            'body' => 'An already-decided decision that is plenty long.',
        ]);

        TicketsServer::actingAs($user)->tool(PromoteNoteTool::class, [
            'ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'blocker',
        ])->assertHasErrors(['Only message notes can be promoted.']);
    }

    #[Test]
    public function watch_tool_watches_unwatches_and_mutes_any_ticket(): void
    {
        $user = User::factory()->create();
        // A ticket the user neither owns nor is assigned to.
        $other = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $other->id,
            'user_id2' => $other->id,
        ]);

        TicketsServer::actingAs($user)->tool(WatchTicketTool::class, [
            'ticket_id' => $ticket->id,
        ])->assertOk();
        $this->assertDatabaseHas('ticket_user_watchers', [
            'ticket_id' => $ticket->id, 'user_id' => $user->id, 'muted' => false,
        ]);

        TicketsServer::actingAs($user)->tool(WatchTicketTool::class, [
            'ticket_id' => $ticket->id, 'action' => 'mute',
        ])->assertOk();
        $this->assertDatabaseHas('ticket_user_watchers', [
            'ticket_id' => $ticket->id, 'user_id' => $user->id, 'muted' => true,
        ]);

        TicketsServer::actingAs($user)->tool(WatchTicketTool::class, [
            'ticket_id' => $ticket->id, 'action' => 'unwatch',
        ])->assertOk();
        $this->assertDatabaseMissing('ticket_user_watchers', [
            'ticket_id' => $ticket->id, 'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function reply_tool_replies_to_top_level_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(ReplyToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'body' => 'Reply via MCP',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'parent_id' => $note->id,
        ]);
    }

    #[Test]
    public function reply_tool_returns_clean_error_for_a_note_on_another_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $otherTicket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $foreignNote = Note::factory()->create(['ticket_id' => $otherTicket->id, 'user_id' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(ReplyToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $foreignNote->id,
            'body' => 'Reply via MCP',
        ]);

        $response->assertHasErrors(['Note not found on this ticket.']);
    }

    #[Test]
    public function reply_tool_returns_the_same_error_for_a_nonexistent_note(): void
    {
        // A note id that exists nowhere must not be distinguishable (via a
        // validation error) from one that belongs to another ticket.
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(ReplyToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => 999999,
            'body' => 'Reply via MCP',
        ]);

        $response->assertHasErrors(['Note not found on this ticket.']);
    }

    #[Test]
    public function edit_note_tool_updates_own_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(EditNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'body' => 'Edited via MCP',
        ]);

        $response->assertOk();
        $this->assertNotNull($note->fresh()->edited_at);
    }

    #[Test]
    public function resolve_note_tool_resolves_blocker(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'blocker',
        ]);

        $response = TicketsServer::actingAs($user)->tool(ResolveNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'resolution_message' => 'Unblocked via MCP',
        ]);

        $response->assertOk();
        $this->assertTrue((bool) $note->fresh()->resolved);
    }

    #[Test]
    public function react_tool_toggles_reaction(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $note = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(ReactToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'emoji' => 'thumbsup',
        ]);

        $response->assertOk();
        $response->assertSee('added');

        $again = TicketsServer::actingAs($user)->tool(ReactToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'emoji' => 'thumbsup',
        ]);

        $again->assertOk();
        $again->assertSee('removed');
    }

    #[Test]
    public function list_milestones_tool_returns_milestones_with_progress(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Alpha Release']);
        $closedIds = Status::closedStatusIds();
        Ticket::factory()->create(['milestone_id' => $milestone->id, 'status_id' => reset($closedIds)]);
        Ticket::factory()->create(['milestone_id' => $milestone->id, 'status_id' => Status::whereNotIn('id', $closedIds)->first()->id]);

        $response = TicketsServer::actingAs($user)->tool(ListMilestonesTool::class, []);

        $response->assertOk();
        $response->assertSee('Alpha Release');
        $response->assertSee('percent_complete');
    }

    #[Test]
    public function get_milestone_tool_returns_detail_and_tickets(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Beta Release']);
        $ticket = Ticket::factory()->create(['milestone_id' => $milestone->id]);

        $response = TicketsServer::actingAs($user)->tool(GetMilestoneTool::class, [
            'milestone_id' => $milestone->id,
        ]);

        $response->assertOk();
        $response->assertSee('Beta Release');
        $response->assertSee($ticket->subject);
    }

    #[Test]
    public function create_milestone_tool_creates_milestone(): void
    {
        $user = User::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateMilestoneTool::class, [
            'name' => 'MCP created milestone',
            'owner_user_id' => $user->id,
            'start_at' => '2026-01-01',
            'due_at' => '2026-02-01',
        ]);

        $response->assertOk();
        $response->assertSee('Milestone created.');
        $this->assertDatabaseHas('milestones', [
            'name' => 'MCP created milestone',
            'owner_user_id' => $user->id,
        ]);
    }

    #[Test]
    public function update_milestone_tool_updates_owned_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Old Name', 'owner_user_id' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(UpdateMilestoneTool::class, [
            'milestone_id' => $milestone->id,
            'name' => 'New Name',
        ]);

        $response->assertOk();
        $response->assertSee('Milestone updated.');
        $this->assertDatabaseHas('milestones', ['id' => $milestone->id, 'name' => 'New Name']);
    }

    #[Test]
    public function update_milestone_tool_rejects_non_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $milestone = Milestone::factory()->create([
            'name' => 'Locked',
            'owner_user_id' => $other->id,
            'scrummaster_user_id' => $other->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(UpdateMilestoneTool::class, [
            'milestone_id' => $milestone->id,
            'name' => 'Hijacked',
        ]);

        $response->assertHasErrors();
        $this->assertDatabaseHas('milestones', ['id' => $milestone->id, 'name' => 'Locked']);
    }

    #[Test]
    public function add_note_tool_creates_mentions_and_notifies(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $mentioned = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => "Ping @[{$mentioned->name}]",
        ]);

        $response->assertOk();
        $note = Note::where('ticket_id', $ticket->id)->where('notetype', 'message')->latest('id')->firstOrFail();

        $this->assertDatabaseHas('mentions', [
            'note_id' => $note->id,
            'user_id' => $mentioned->id,
        ]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $mentioned->id]);
    }

    #[Test]
    public function add_note_tool_writes_a_changelog_when_status_changes(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $newStatus = Status::factory()->create(['name' => 'Escalated']);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'status_id' => $newStatus->id,
        ]);

        $response->assertOk();
        $this->assertSame($newStatus->id, $ticket->fresh()->status_id);
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function reply_to_note_tool_creates_reply(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => null,
        ]);

        $response = TicketsServer::actingAs($user)->tool(ReplyToNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $parent->id,
            'body' => 'Reply from MCP',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body_markdown' => 'Reply from MCP',
        ]);
    }

    #[Test]
    public function update_ticket_tool_creates_changelog(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $newStatus = Status::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'status_id' => $newStatus->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }
}
