<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaletteSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:500'],
        ];
    }
}
