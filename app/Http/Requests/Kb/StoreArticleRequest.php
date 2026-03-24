<?php

namespace App\Http\Requests\Kb;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body_markdown' => 'required|string',
            'category_id' => 'required|integer|exists:kb_categories,id',
            'visibility' => 'required|in:public,internal,restricted',
            'commit_message' => 'required|string|max:255',
            'tags' => 'required|array|min:1',
            'tags.*' => 'integer|exists:kb_tags,id',
            'status' => 'nullable|in:draft,verified,deprecated',
            'permitted_users' => 'nullable|array',
            'permitted_users.*' => 'integer|exists:users,id',
        ];
    }
}
