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
            'type_id' => $this->type_id,
            'importance_id' => $this->importance_id,
            'milestone_id' => $this->milestone_id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'user_id2' => $this->user_id2,
            'estimate' => $this->estimate,
            'storypoints' => $this->storypoints,
            'due_at' => $this->due_at,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
