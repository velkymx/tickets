<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'nullable|in:me,any',
            'status' => 'nullable|integer|exists:statuses,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
