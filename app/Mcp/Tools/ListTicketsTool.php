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

#[Description('List tickets assigned to the authenticated user, newest first. Optionally filter by status ID, list unassigned tickets instead, or include pulse health summaries.')]
#[IsReadOnly]
#[IsIdempotent]
class ListTicketsTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'status_id' => 'nullable|integer|exists:statuses,id',
            'unassigned' => 'nullable|boolean',
            'include_pulse' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $includePulse = (bool) ($validated['include_pulse'] ?? false);

        $eagerLoads = ['status', 'importance'];
        if ($includePulse) {
            $eagerLoads = array_merge($eagerLoads, ['notes.user', 'notes.supersedes', 'notes.replies', 'assignee']);
        }

        $query = Ticket::with($eagerLoads);

        if (! empty($validated['unassigned'])) {
            $query->where(function ($q) {
                $q->whereNull('user_id2')->orWhere('user_id2', 0);
            });
        } else {
            $query->where('user_id2', $user->id);
        }

        if (! empty($validated['status_id'])) {
            $query->where('status_id', $validated['status_id']);
        }

        $tickets = $query->orderBy('created_at', 'DESC')->paginate($perPage);
        $pulseService = $includePulse ? app(TicketPulseService::class) : null;

        $data = $tickets->map(function ($ticket) use ($includePulse, $pulseService) {
            $item = [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'estimate' => $ticket->estimate,
                'status' => $ticket->status->name ?? null,
                'importance' => $ticket->importance->name ?? null,
                'due_at' => $ticket->due_at,
                'closed_at' => $ticket->closed_at,
                'created_at' => $ticket->created_at->toDateString(),
            ];

            if ($includePulse) {
                $pulse = $pulseService->getPulse($ticket);
                $item['pulse_summary'] = [
                    'execution_state' => $pulse->execution_state,
                    'is_blocked' => $pulse->is_blocked,
                    'has_open_actions' => ! empty($pulse->next_action['id']),
                    'has_decisions' => $pulse->latest_decision !== null,
                    'unresolved_thread_count' => count($pulse->open_threads),
                ];
            }

            return $item;
        });

        return Response::json([
            'data' => $data,
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status_id' => $schema->integer()->description('Filter by status ID (see lookups).'),
            'unassigned' => $schema->boolean()->description('Set true to list unassigned tickets instead of your own.'),
            'include_pulse' => $schema->boolean()->description('Include a pulse health summary on each ticket.'),
            'per_page' => $schema->integer()->description('Items per page, max 100. Default 20.'),
        ];
    }
}
