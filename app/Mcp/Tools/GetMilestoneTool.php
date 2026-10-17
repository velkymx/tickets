<?php

namespace App\Mcp\Tools;

use App\Models\Milestone;
use App\Models\Status;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get full detail for one milestone: metadata, owner and scrum master, completion stats, and a summary of its tickets.')]
#[IsReadOnly]
#[IsIdempotent]
class GetMilestoneTool extends MilestoneTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'milestone_id' => 'required|integer|exists:milestones,id',
        ]);

        $milestone = Milestone::with([
            'owner', 'scrummaster',
            'tickets' => fn ($q) => $q->with(['status', 'type', 'importance', 'assignee']),
        ])->find($validated['milestone_id']);

        if (! $milestone) {
            return $this->milestoneNotFound();
        }

        $closedStatusIds = Status::closedStatusIds();
        $total = $milestone->tickets->count();
        $completed = $milestone->tickets->whereIn('status_id', $closedStatusIds)->count();

        return Response::json([
            'id' => $milestone->id,
            'name' => $milestone->name,
            'description' => strip_tags($milestone->description ?? ''),
            'owner' => $milestone->owner->name ?? null,
            'scrummaster' => $milestone->scrummaster->name ?? null,
            'start_at' => $milestone->start_at?->toDateString(),
            'due_at' => $milestone->due_at?->toDateString(),
            'end_at' => $milestone->end_at?->toDateString(),
            'total_tickets' => $total,
            'completed_tickets' => $completed,
            'percent_complete' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'tickets' => $milestone->tickets->map(fn ($ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->name ?? null,
                'type' => $ticket->type->name ?? null,
                'importance' => $ticket->importance->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
                'storypoints' => $ticket->storypoints,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'milestone_id' => $schema->integer()->description('The milestone ID.')->required(),
        ];
    }
}
