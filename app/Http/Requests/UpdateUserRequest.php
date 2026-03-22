<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.auth()->id(),
            'phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|timezone|max:100',
            'theme' => 'nullable|string|in:simplex,darkly',
            'bio' => 'nullable|string|max:2000',
            'avatar' => 'nullable|image|max:2048',
        ];
    }
}
