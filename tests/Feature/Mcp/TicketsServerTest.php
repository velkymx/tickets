<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddNoteTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\EditNoteTool;
use App\Mcp\Tools\GetLookupsTool;
use App\Mcp\Tools\GetPulseTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\ReactToNoteTool;
use App\Mcp\Tools\ReplyToNoteTool;
use App\Mcp\Tools\ResolveNoteTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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
    public function lookups_tool_rejects_unauthenticated_calls(): void
    {
        $response = TicketsServer::tool(GetLookupsTool::class, []);

        $response->assertHasErrors(['Unauthenticated']);
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
    public function server_acknowledges_initialized_lifecycle_method(): void
    {
        $server = new TicketsServer(new \Laravel\Mcp\Server\Transport\StdioTransport);

        $boot = new \ReflectionMethod($server, 'boot');
        $boot->setAccessible(true);
        $boot->invoke($server);

        $methods = new \ReflectionProperty($server, 'methods');
        $methods->setAccessible(true);

        $this->assertArrayHasKey('notifications/initialized', $methods->getValue($server));
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
        $this->assertDatabaseMissing('notes', ['ticket_id' => $ticket->id]);
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
}
