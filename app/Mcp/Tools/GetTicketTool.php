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

#[Description('Get full detail for one ticket: metadata, visible notes with replies and reactions, and pulse health state.')]
#[IsReadOnly]
#[IsIdempotent]
class GetTicketTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
        ]);

        $ticket = Ticket::with([
            'status', 'type', 'importance', 'milestone', 'project', 'assignee',
            'notes' => fn ($q) => $q->where('hide', 0)
                ->whereNull('parent_id')
                ->orderBy('created_at', 'asc')
                ->with(['user', 'replies.user', 'reactions', 'attachments']),
        ])->find($validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $pulse = app(TicketPulseService::class)->getPulse($ticket)->toArray();

        return Response::json([
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => strip_tags($ticket->description ?? ''),
            'status' => $ticket->status->name ?? null,
            'type' => $ticket->type->name ?? null,
            'importance' => $ticket->importance->name ?? null,
            'project' => $ticket->project->name ?? null,
            'milestone' => $ticket->milestone->name ?? null,
            'assignee' => $ticket->assignee->name ?? null,
            'estimate' => $ticket->estimate,
            'storypoints' => $ticket->storypoints,
            'due_at' => $ticket->due_at,
            'closed_at' => $ticket->closed_at,
            'pulse' => [
                'execution_state' => $pulse['execution_state'] ?? null,
                'is_blocked' => $pulse['is_blocked'] ?? null,
                'blocker_reason' => $pulse['blocker_reason'] ?? null,
                'next_action' => $pulse['next_action'] ?? null,
                'latest_decision' => $pulse['latest_decision'] ?? null,
            ],
            'notes' => $ticket->notes->map(fn ($note) => [
                'id' => $note->id,
                'user' => $note->user->name ?? null,
                'notetype' => $note->notetype ?? 'message',
                'body' => strip_tags($note->body ?? ''),
                'hours' => $note->hours,
                'resolved' => (bool) $note->resolved,
                'resolved_by' => $note->resolved_by,
                'resolution_message' => $note->resolution_message,
                'edited_at' => $note->edited_at?->toISOString(),
                'created_at' => $note->created_at->toDateTimeString(),
                'reactions' => $note->reactions->groupBy('emoji')->map(fn ($group) => [
                    'count' => $group->count(),
                ]),
                'replies' => $note->replies->map(fn ($reply) => [
                    'id' => $reply->id,
                    'user' => $reply->user->name ?? null,
                    'body' => strip_tags($reply->body ?? ''),
                ])->values(),
            ])->values(),
        ]);
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
