<?php

namespace App\Mcp\Tools;

use App\Models\Status;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a ticket you own or are assigned to: subject, description, or status. Closing via a closed status sets closed_at.')]
class UpdateTicketTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'subject' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'nullable|integer|exists:statuses,id',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        if (array_key_exists('subject', $validated) && $validated['subject'] !== null) {
            $ticket->subject = $validated['subject'];
        }

        if (array_key_exists('description', $validated)) {
            $ticket->description = $validated['description'] ?? '';
        }

        if (! empty($validated['status_id']) && $validated['status_id'] != $ticket->status_id) {
            $ticket->status_id = $validated['status_id'];
            $ticket->closed_at = Status::isClosed($validated['status_id']) ? now() : null;
        }

        $ticket->save();
        $ticket->load(['status', 'assignee']);

        return Response::json([
            'message' => 'Ticket updated.',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
            ],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'subject' => $schema->string()->description('New title.'),
            'description' => $schema->string()->description('New description, plain text.'),
            'status_id' => $schema->integer()->description('Move the ticket to this status ID (see lookups).'),
        ];
    }
}
