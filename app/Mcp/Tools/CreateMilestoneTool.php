<?php

namespace App\Mcp\Tools;

use App\Models\Milestone;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a milestone. Resolve owner and scrum master user IDs with the lookups tool first.')]
class CreateMilestoneTool extends MilestoneTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'scrummaster_user_id' => 'nullable|integer|exists:users,id',
            'start_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:start_at',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ]);

        $milestone = Milestone::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner_user_id' => $validated['owner_user_id'] ?? null,
            'scrummaster_user_id' => $validated['scrummaster_user_id'] ?? null,
            'start_at' => $this->toDate($validated['start_at'] ?? null),
            'due_at' => $this->toDate($validated['due_at'] ?? null),
            'end_at' => $this->toDate($validated['end_at'] ?? null),
        ]);

        return Response::json([
            'message' => 'Milestone created.',
            'milestone' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'link' => "/milestone/show/{$milestone->id}",
            ],
        ]);
    }

    private function toDate(?string $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Milestone name.')->required(),
            'description' => $schema->string()->description('Description, plain text.'),
            'owner_user_id' => $schema->integer()->description('Product owner user ID (see lookups).'),
            'scrummaster_user_id' => $schema->integer()->description('Scrum master user ID (see lookups).'),
            'start_at' => $schema->string()->description('Start date, YYYY-MM-DD.'),
            'due_at' => $schema->string()->description('Due date, YYYY-MM-DD. Must be on or after the start date.'),
            'end_at' => $schema->string()->description('Release date, YYYY-MM-DD. Must be on or after the start date.'),
        ];
    }
}
