<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'tickets.*' => 'required|in:on,1,true',
            'type_id' => 'nullable|integer|exists:types,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'user_id2' => 'nullable|integer|exists:users,id',
            'release_id' => 'nullable|integer|exists:releases,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ticketKeys = array_keys($this->input('tickets', []));
            $existingTickets = Ticket::whereIn('id', $ticketKeys)->pluck('id')->toArray();
            $missingTickets = array_diff($ticketKeys, array_map('strval', $existingTickets));

            if (! empty($missingTickets)) {
                $validator->errors()->add('tickets', 'Tickets '.implode(', ', $missingTickets).' do not exist.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'tickets.required' => 'At least one ticket must be selected.',
            'tickets.*.exists' => 'One or more selected tickets do not exist.',
        ];
    }
}
