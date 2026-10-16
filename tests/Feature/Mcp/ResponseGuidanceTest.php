<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddNoteTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\GetPulseTool;
use App\Mcp\Tools\ResolveNoteTool;
use App\Mcp\Tools\SearchTicketsTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ResponseGuidanceTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function create_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateTicketTool::class, [
            'subject' => 'Guided ticket',
            'type_id' => 3,
            'importance_id' => 3,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
        ]);

        $response->assertOk();
        $response->assertSee('guidance');
        $response->assertSee('Guided ticket');
    }

    #[Test]
    public function search_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['user_id2' => $user->id, 'subject' => 'Guided search hit']);

        $response = TicketsServer::actingAs($user)->tool(SearchTicketsTool::class, ['query' => 'guided']);

        $response->assertOk();
        $response->assertSee('guidance');
    }

    #[Test]
    public function pulse_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(GetPulseTool::class, ['ticket_id' => $ticket->id]);

        $response->assertOk();
        $response->assertSee('guidance');
    }

    #[Test]
    public function update_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'subject' => 'Guided update',
        ]);

        $response->assertOk();
        $response->assertSee('guidance');
        $response->assertSee('Guided update');
    }

    #[Test]
    public function add_note_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(AddNoteTool::class, [
            'ticket_id' => $ticket->id,
            'body' => 'Guided note',
        ]);

        $response->assertOk();
        $response->assertSee('guidance');
    }

    #[Test]
    public function resolve_tool_returns_next_step_guidance(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id]);

        $response = TicketsServer::actingAs($user)->tool(ResolveNoteTool::class, [
            'ticket_id' => $ticket->id,
            'note_id' => $note->id,
            'resolution_message' => 'Settled',
        ]);

        $response->assertOk();
        $response->assertSee('guidance');
    }

    #[Test]
    public function every_non_excepted_tool_emits_guidance(): void
    {
        // Inverted allowlist: read-only tools with no follow-up action are
        // the deliberate exceptions. Any other tool returning bare
        // Response::json fails, so a new tool without guidance is caught.
        $excepted = [
            'GetKbArticleTool',
            'GetKbLookupsTool',
            'GetLookupsTool',
            'GetMilestoneTool',
            'GetTicketTool',
        ];

        $files = glob(app_path('Mcp/Tools/*Tool.php')) ?: [];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $class = basename($file, '.php');

            if (in_array($class, $excepted, true)) {
                continue;
            }

            $source = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                'return Response::json(',
                $source,
                "{$class} returns bare JSON without guidance"
            );
        }
    }
}
