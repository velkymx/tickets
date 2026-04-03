<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|integer|exists:tickets,id',
            'note' => 'nullable|string|max:65535',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'hours' => 'nullable|numeric|min:0|max:999',
            'notetype' => 'nullable|string|in:message,blocker,decision,action,changelog',
        ];
    }
}
