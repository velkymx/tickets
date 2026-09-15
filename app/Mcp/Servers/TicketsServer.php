<?php

namespace App\Mcp\Servers;

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
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Tickets Server')]
#[Version('1.0.0')]
#[Instructions('This server manages tickets: list and read assigned tickets, create tickets, add notes with slash commands, and resolve lookup IDs. Always call get-lookups first to resolve type, importance, project, milestone, status, and user names to IDs. Web access requires an Authorization Bearer API token generated at Profile -> Edit Profile -> API Token.')]
class TicketsServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        GetLookupsTool::class,
        ListTicketsTool::class,
        GetTicketTool::class,
        GetPulseTool::class,
        CreateTicketTool::class,
        UpdateTicketTool::class,
        AddNoteTool::class,
        ReplyToNoteTool::class,
        EditNoteTool::class,
        ResolveNoteTool::class,
        ReactToNoteTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
