<?php

namespace App\Mcp\Tools;

use App\Models\Status;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a ticket. Resolve type, importance, project, and milestone IDs with the lookups tool first. The ticket is assigned to the authenticated user.')]
class CreateTicketTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|integer|exists:types,id',
            'importance_id' => 'required|integer|exists:importances,id',
            'project_id' => 'required|integer|exists:projects,id',
            'milestone_id' => 'required|integer|exists:milestones,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
        ]);

        $ticket = Ticket::create([
            'subject' => $validated['subject'],
            'description' => $validated['description'] ?? '',
            'type_id' => $validated['type_id'],
            'importance_id' => $validated['importance_id'],
            'project_id' => $validated['project_id'],
            'milestone_id' => $validated['milestone_id'],
            'due_at' => $validated['due_at'] ?? null,
            'estimate' => $validated['estimate'] ?? 0,
            'storypoints' => $validated['storypoints'] ?? 0,
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $validated['status_id'] ?? Status::orderBy('id')->first()?->id,
        ]);

        return Response::json([
            'message' => 'Ticket created.',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'link' => "/tickets/{$ticket->id}",
            ],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'subject' => $schema->string()->description('Ticket title.')->required(),
            'description' => $schema->string()->description('Full details, plain text.'),
            'type_id' => $schema->integer()->description('Type ID (see lookups).')->required(),
            'importance_id' => $schema->integer()->description('Importance ID (see lookups).')->required(),
            'project_id' => $schema->integer()->description('Project ID (see lookups).')->required(),
            'milestone_id' => $schema->integer()->description('Milestone ID (see lookups).')->required(),
            'status_id' => $schema->integer()->description('Status ID. Defaults to the first status.'),
            'due_at' => $schema->string()->description('Due date, YYYY-MM-DD.'),
            'estimate' => $schema->number()->description('Time estimate in hours.'),
            'storypoints' => $schema->integer()->description('Story points.'),
        ];
    }
}
