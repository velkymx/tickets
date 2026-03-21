<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchUpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tickets' => 'required|array|min:1',
            'tickets.*' => 'required|integer|exists:tickets,id',
            'type_id' => 'nullable|integer|exists:types,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'user_id2' => 'nullable|integer|exists:users,id',
            'release_id' => 'nullable|integer|exists:releases,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tickets.required' => 'At least one ticket must be selected.',
            'tickets.*.exists' => 'One or more selected tickets do not exist.',
        ];
    }
}
