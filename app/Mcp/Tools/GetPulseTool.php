<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Services\TicketPulseService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get the full pulse health state for a ticket: execution state (ON TRACK, AT RISK, BLOCKED, IDLE), blocker reason, next action, latest decision, and open threads. Check this before acting on a ticket.')]
#[IsReadOnly]
#[IsIdempotent]
class GetPulseTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $ticket = Ticket::find($validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $pulse = app(TicketPulseService::class)->getPulse($ticket)->toArray();

        return Response::json(['data' => $pulse]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
        ];
    }
}
