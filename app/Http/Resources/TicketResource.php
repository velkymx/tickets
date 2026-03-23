<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status_id' => $this->status_id,
            'status_name' => $this->whenLoaded('status', fn () => $this->status?->name, $this->status?->name),
            'type_id' => $this->type_id,
            'importance_id' => $this->importance_id,
            'milestone_id' => $this->milestone_id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'user_id2' => $this->user_id2,
            'assignee_name' => $this->whenLoaded('assignee', fn () => $this->assignee?->name, $this->assignee?->name),
            'estimate' => $this->estimate,
            'storypoints' => $this->storypoints,
            'due_at' => $this->due_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'note_count' => $this->whenCounted('notes', $this->notes_count, fn () => $this->notes()->count()),
            'notetype_summary' => $this->whenLoaded('notes', function () {
                return $this->notes->groupBy('notetype')->map(fn ($notes) => $notes->count())->toArray();
            }),
        ];
    }
}
