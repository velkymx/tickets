<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'nullable|integer|exists:types,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'user_id2' => 'nullable|integer|exists:users,id',
            'due_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'actual' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
        ];
    }
}
