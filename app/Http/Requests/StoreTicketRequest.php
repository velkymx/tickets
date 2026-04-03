<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'nullable|integer|exists:types,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0|max:99999',
            'storypoints' => 'nullable|integer|min:0|max:99999',
        ];
    }
}
