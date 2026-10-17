<?php

namespace App\Mcp\Tools;

use App\Models\Milestone;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a milestone you own or scrum-master: name, description, owner, scrum master, start/due/release dates. Only the fields you pass are changed.')]
class UpdateMilestoneTool extends MilestoneTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'milestone_id' => 'required|integer|exists:milestones,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'scrummaster_user_id' => 'nullable|integer|exists:users,id',
            'start_at' => 'nullable|date',
            'due_at' => 'nullable|date',
            'end_at' => 'nullable|date',
        ]);

        $milestone = Milestone::find($validated['milestone_id']);

        if (! $milestone) {
            return $this->milestoneNotFound();
        }

        if (! Gate::forUser($user)->allows('update', $milestone)) {
            return $this->notAuthorized();
        }

        if (! empty($validated['name'])) {
            $milestone->name = $validated['name'];
        }

        if (array_key_exists('description', $validated)) {
            $milestone->description = $validated['description'];
        }

        foreach (['owner_user_id', 'scrummaster_user_id'] as $fk) {
            if (array_key_exists($fk, $validated)) {
                $milestone->{$fk} = $validated[$fk];
            }
        }

        foreach (['start_at', 'due_at', 'end_at'] as $date) {
            if (array_key_exists($date, $validated)) {
                $milestone->{$date} = $validated[$date] ? Carbon::parse($validated[$date])->format('Y-m-d') : null;
            }
        }

        $milestone->save();
        $milestone->load(['owner', 'scrummaster']);

        return $this->respond([
            'message' => 'Milestone updated.',
            'milestone' => [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'owner' => $milestone->owner->name ?? null,
                'scrummaster' => $milestone->scrummaster->name ?? null,
            ],
        ], 'Milestone updated. Call get-milestone-tool to confirm.');
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'milestone_id' => $schema->integer()->description('The milestone ID.')->required(),
            'name' => $schema->string()->description('New name.'),
            'description' => $schema->string()->description('New description, plain text.'),
            'owner_user_id' => $schema->integer()->description('Reassign the product owner to this user ID (see lookups).'),
            'scrummaster_user_id' => $schema->integer()->description('Reassign the scrum master to this user ID (see lookups).'),
            'start_at' => $schema->string()->description('Start date, YYYY-MM-DD.'),
            'due_at' => $schema->string()->description('Due date, YYYY-MM-DD.'),
            'end_at' => $schema->string()->description('Release date, YYYY-MM-DD.'),
        ];
    }
}
