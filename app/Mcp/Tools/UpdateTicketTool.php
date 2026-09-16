<?php

namespace App\Mcp\Tools;

use App\Models\Status;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a ticket you own or are assigned to: subject, description, status, assignee, type, importance, project, milestone, due date, estimate, story points, or actual hours. Closing via a closed status sets closed_at.')]
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
            'assignee_id' => 'nullable|integer|exists:users,id',
            'type_id' => 'nullable|integer|exists:types,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
            'actual' => 'nullable|numeric|min:0',
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

        if (! empty($validated['assignee_id'])) {
            $ticket->user_id2 = $validated['assignee_id'];
        }

        foreach (['type_id', 'importance_id', 'project_id', 'milestone_id'] as $fk) {
            if (! empty($validated[$fk])) {
                $ticket->{$fk} = $validated[$fk];
            }
        }

        // Numeric/date fields use array_key_exists so a caller can clear a due
        // date (null) or set a zero estimate, which an empty() check would drop.
        foreach (['due_at', 'estimate', 'storypoints', 'actual'] as $field) {
            if (array_key_exists($field, $validated)) {
                $ticket->{$field} = $validated[$field];
            }
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
            'assignee_id' => $schema->integer()->description('Reassign the ticket to this user ID (see lookups).'),
            'type_id' => $schema->integer()->description('Change the ticket type ID (see lookups).'),
            'importance_id' => $schema->integer()->description('Change the importance/priority ID (see lookups).'),
            'project_id' => $schema->integer()->description('Move the ticket to this project ID (see lookups).'),
            'milestone_id' => $schema->integer()->description('Move the ticket to this milestone ID (see lookups).'),
            'due_at' => $schema->string()->description('Due date, YYYY-MM-DD. Pass null to clear.'),
            'estimate' => $schema->number()->description('Time estimate in hours.'),
            'storypoints' => $schema->integer()->description('Story points.'),
            'actual' => $schema->number()->description('Actual hours spent.'),
        ];
    }
}
