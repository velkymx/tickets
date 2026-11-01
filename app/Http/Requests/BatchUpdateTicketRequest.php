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

    protected function prepareForValidation(): void
    {
        // The batch form sends 0 for "No Change" on every select. Normalize to
        // null so the nullable rules treat it as untouched instead of failing
        // the exists check on a nonexistent id 0.
        $normalized = [];

        foreach (['type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2', 'release_id'] as $field) {
            if ($this->has($field) && in_array((string) $this->input($field), ['0', ''], true)) {
                $normalized[$field] = null;
            }
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'tickets' => 'required|array|min:1',
            'tickets.*' => 'required',
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
