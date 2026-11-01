<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\AddNoteTool;
use App\Mcp\Tools\CreateKbArticleTool;
use App\Mcp\Tools\CreateMilestoneTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\EditNoteTool;
use App\Mcp\Tools\GetKbArticleTool;
use App\Mcp\Tools\GetKbLookupsTool;
use App\Mcp\Tools\GetLookupsTool;
use App\Mcp\Tools\GetMilestoneTool;
use App\Mcp\Tools\GetPulseTool;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\KbSearchTool;
use App\Mcp\Tools\ListMilestonesTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\ModerateNoteTool;
use App\Mcp\Tools\PromoteNoteTool;
use App\Mcp\Tools\ReactToNoteTool;
use App\Mcp\Tools\ReplyToNoteTool;
use App\Mcp\Tools\ResolveNoteTool;
use App\Mcp\Tools\SearchTicketsTool;
use App\Mcp\Tools\UpdateKbArticleTool;
use App\Mcp\Tools\UpdateMilestoneTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Mcp\Tools\WatchTicketTool;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
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
    public function every_non_excepted_tool_emits_guidance(): void
    {
        $excepted = [
            GetKbArticleTool::class,
            GetKbLookupsTool::class,
            GetLookupsTool::class,
            GetMilestoneTool::class,
            GetTicketTool::class,
        ];

        $tools = (new \ReflectionClass(TicketsServer::class))->getDefaultProperties()['tools'] ?? [];

        $this->assertNotEmpty($tools);

        $user = User::factory()->create();
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
        $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'notetype' => 'message']);
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->public()->create([
            'user_id' => $user->id, 'owner_id' => $user->id, 'category_id' => $category->id,
        ]);

        foreach ($tools as $tool) {
            if (in_array($tool, $excepted, true)) {
                continue;
            }

            $args = match ($tool) {
                SearchTicketsTool::class => ['query' => 'guided'],
                GetPulseTool::class => ['ticket_id' => $ticket->id],
                CreateTicketTool::class => [
                    'subject' => 'Guided ticket', 'type_id' => 3, 'importance_id' => 3,
                    'project_id' => $project->id, 'milestone_id' => $milestone->id,
                ],
                UpdateTicketTool::class => ['ticket_id' => $ticket->id, 'subject' => 'Guided update'],
                ListMilestonesTool::class => [],
                ListTicketsTool::class => [],
                CreateMilestoneTool::class => ['name' => 'Guided milestone'],
                UpdateMilestoneTool::class => ['milestone_id' => $milestone->id, 'name' => 'Guided rename'],
                AddNoteTool::class => ['ticket_id' => $ticket->id, 'body' => 'Guided note'],
                ReplyToNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'body' => 'Guided reply'],
                EditNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'body' => 'Guided edit with enough words to pass validation rules'],
                ResolveNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'resolution_message' => 'Settled'],
                ReactToNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'emoji' => 'thumbsup'],
                ModerateNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'action' => 'pin'],
                PromoteNoteTool::class => ['ticket_id' => $ticket->id, 'note_id' => $note->id, 'type' => 'decision'],
                WatchTicketTool::class => ['ticket_id' => $ticket->id],
                KbSearchTool::class => ['query' => 'guided'],
                CreateKbArticleTool::class => [
                    'title' => 'Guided article', 'body_markdown' => 'Body', 'category_id' => $category->id,
                    'visibility' => 'public', 'commit_message' => 'First', 'tags' => [$tag->id],
                ],
                UpdateKbArticleTool::class => [
                    'id' => $article->id, 'title' => 'Guided retitle', 'body_markdown' => 'Body',
                    'category_id' => $category->id, 'visibility' => 'public',
                    'commit_message' => 'Second', 'tags' => [$tag->id],
                ],
                default => $this->fail("No args mapped for registered tool {$tool}"),
            };

            TicketsServer::actingAs($user)->tool($tool, $args)->assertOk()->assertSee('guidance');
        }
    }
}
