<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Cache;

class TicketPulseService
{
    public function getPulse(Ticket $ticket): array
    {
        return Cache::remember("ticket_pulse:{$ticket->id}", now()->addHours(24), function () use ($ticket) {
            return $this->computePulse($ticket);
        });
    }

    public function invalidatePulse(int $ticketId): void
    {
        Cache::forget("ticket_pulse:{$ticketId}");
    }

    protected function computePulse(Ticket $ticket): array
    {
        // Placeholder for phase 3.2 logic
        return [
            'id' => $ticket->id,
            'status' => $ticket->status->name,
            'is_blocked' => false,
            'blocker_reason' => null,
            'owner_label' => 'Unassigned',
            'next_action' => null,
            'latest_decision' => null,
            'execution_state' => 'IDLE',
        ];
    }
}
