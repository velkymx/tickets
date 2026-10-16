<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership comes from the authenticated user (the controller
        // builds through Auth::user()->savedViews()), never from input,
        // so there is no user_id field to authorize here.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('saved_views', 'name')->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'query' => 'required|string|max:500',
        ];
    }
}
