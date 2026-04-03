<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'body' => ['nullable', 'string'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        if (! empty($validated['started_at'])) {
            $validated['started_at'] = Carbon::parse($validated['started_at'])->format('Y-m-d');
        } else {
            $validated['started_at'] = null;
        }

        if (! empty($validated['completed_at'])) {
            $validated['completed_at'] = Carbon::parse($validated['completed_at'])->format('Y-m-d');
        } else {
            $validated['completed_at'] = null;
        }

        return $validated;
    }
}
