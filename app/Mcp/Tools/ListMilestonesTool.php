<?php

namespace App\Mcp\Tools;

use App\Models\Milestone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List milestones with their dates, owner, scrum master, and ticket completion progress.')]
#[IsReadOnly]
#[IsIdempotent]
class ListMilestonesTool extends MilestoneTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        $milestones = Milestone::with(['owner', 'scrummaster'])
            ->withCount([
                'tickets as total_tickets',
                'tickets as completed_tickets' => fn ($q) => $q->closed(),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($milestone) => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'start_at' => $milestone->start_at?->toDateString(),
                'due_at' => $milestone->due_at?->toDateString(),
                'end_at' => $milestone->end_at?->toDateString(),
                'owner' => $milestone->owner->name ?? null,
                'scrummaster' => $milestone->scrummaster->name ?? null,
                'total_tickets' => $milestone->total_tickets,
                'completed_tickets' => $milestone->completed_tickets,
                'percent_complete' => $milestone->total_tickets > 0
                    ? (int) round($milestone->completed_tickets / $milestone->total_tickets * 100)
                    : 0,
            ]);

        return $this->respond([
            'milestones' => $milestones,
        ], 'Milestones newest first. Call get-milestone-tool for tickets.');
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
