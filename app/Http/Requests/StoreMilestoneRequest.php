<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scrummaster_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        foreach (['start_at', 'due_at', 'end_at'] as $date) {
            if (isset($validated[$date]) && $validated[$date] !== '') {
                $validated[$date] = Carbon::parse($validated[$date])->format('Y-m-d');
            } else {
                $validated[$date] = null;
            }
        }

        return $validated;
    }
}
