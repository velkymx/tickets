<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scrummaster_user_id' => 'nullable|integer|exists:users,id',
            'owner_user_id' => 'nullable|integer|exists:users,id',
            'start_at' => 'nullable|date',
            'due_at' => 'nullable|date|after_or_equal:start_at',
            'end_at' => 'nullable|date|after_or_equal:start_at',
        ];
    }
}
