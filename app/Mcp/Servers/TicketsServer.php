<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\CloseOutTicketPrompt;
use App\Mcp\Prompts\StandupDigestPrompt;
use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\ServerMethods\InitializedAck;
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
use App\Mcp\Tools\UpdateKbArticleTool;
use App\Mcp\Tools\UpdateMilestoneTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Mcp\Tools\WatchTicketTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Tickets Server')]
#[Version('1.0.0')]
#[Instructions('This server manages tickets, milestones, and the knowledge base: list and read assigned tickets, create tickets, add notes with slash commands, list/read/create/update milestones, and search/read/write KB articles. Call get-lookups-tool first for ticket, milestone, and user IDs and get-kb-lookups-tool for KB category/tag IDs before creating or updating. Web access requires an Authorization Bearer API token generated at Profile -> Edit Profile -> API Token.')]
class TicketsServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetLookupsTool::class,
        ListTicketsTool::class,
        GetTicketTool::class,
        GetPulseTool::class,
        CreateTicketTool::class,
        UpdateTicketTool::class,
        ListMilestonesTool::class,
        GetMilestoneTool::class,
        CreateMilestoneTool::class,
        UpdateMilestoneTool::class,
        AddNoteTool::class,
        ReplyToNoteTool::class,
        EditNoteTool::class,
        ResolveNoteTool::class,
        ReactToNoteTool::class,
        ModerateNoteTool::class,
        PromoteNoteTool::class,
        WatchTicketTool::class,
        GetKbLookupsTool::class,
        KbSearchTool::class,
        GetKbArticleTool::class,
        CreateKbArticleTool::class,
        UpdateKbArticleTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        TriageTicketPrompt::class,
        StandupDigestPrompt::class,
        CloseOutTicketPrompt::class,
    ];

    protected function boot(): void
    {
        // Accept the MCP lifecycle ack instead of answering -32601.
        $this->addMethod('notifications/initialized', InitializedAck::class);
    }
}
