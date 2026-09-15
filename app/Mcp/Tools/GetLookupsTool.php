<?php

namespace App\Mcp\Tools;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get ID lookups for statuses, types, importance levels, projects, milestones, and users. Call this first to resolve names to IDs before creating tickets.')]
#[IsReadOnly]
#[IsIdempotent]
class GetLookupsTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        return Response::json([
            'statuses' => Status::orderBy('id')->get(['id', 'name']),
            'types' => Type::orderBy('name')->get(['id', 'name']),
            'importance' => Importance::orderBy('name')->get(['id', 'name']),
            'projects' => Project::where('active', 1)->orderBy('name')->get(['id', 'name']),
            'milestones' => Milestone::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
