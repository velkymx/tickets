<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Services\TicketQueryParser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search your tickets with a GitLab-style query: key:value filters (status, importance, type, project, milestone, assignee) plus free text matching the subject. Same grammar and active-status default as the web lists. Scoped to tickets you own or are assigned to.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchTicketsTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'query' => 'required|string|max:500',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = app(TicketQueryParser::class)->parse($validated['query']);
        $filters['status_id'] ??= 'none'; // default to active statuses, like the web lists

        $tickets = Ticket::filter($filters)
            ->where(fn ($query) => $query->where('user_id2', $user->id)->orWhere('user_id', $user->id))
            ->with(['status', 'importance', 'project', 'assignee'])
            ->orderBy('updated_at', 'DESC')
            ->paginate($validated['per_page'] ?? 20);

        $data = $tickets->map(fn (Ticket $ticket) => [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status->name ?? null,
            'importance' => $ticket->importance->name ?? null,
            'project' => $ticket->project->name ?? null,
            'assignee' => $ticket->assignee->name ?? null,
        ]);

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
            'query' => $schema->string()->description('GitLab-style query, e.g. status:active importance:blocker assignee:me.')->required(),
            'per_page' => $schema->integer()->description('Items per page, max 100. Default 20.'),
        ];
    }
}
