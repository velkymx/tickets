<?php

use App\Mcp\Servers\TicketsServer;
use Laravel\Mcp\Facades\Mcp;

// Remote AI clients authenticate with the same Bearer API token as the REST API.
Mcp::web('/mcp/tickets', TicketsServer::class)->middleware(['api.token', 'throttle:api'])->name('mcp.tickets');

// Local stdio server for on-machine assistants. No HTTP auth layer,
// so set MCP_USER_ID to the acting user id when starting the client.
Mcp::local('tickets', TicketsServer::class);
