<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'type_id' => 'nullable|integer|exists:types,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'user_id2' => 'nullable|integer|exists:users,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'q' => 'nullable|string|max:255',
            'perpage' => 'nullable|integer|in:10,25,50,100',
        ];
    }
}
