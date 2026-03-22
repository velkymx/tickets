<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Note;
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
        $activeBlocker = $ticket->notes()
            ->where('notetype', 'blocker')
            ->where('resolved', false)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $latestAction = $ticket->notes()
            ->where('notetype', 'action')
            ->where('resolved', false)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $latestDecision = $ticket->notes()
            ->where('notetype', 'decision')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('notes as superseded')
                    ->whereColumn('superseded.supersedes_id', 'notes.id');
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $status = $activeBlocker ? 'BLOCKED' : $ticket->status->name;

        $assigneeName = $ticket->assignee->name ?? 'Unassigned';
        $ownerLabel = $assigneeName === 'Unassigned' ? 'Unassigned' : "Owner: {$assigneeName}";

        return [
            'id' => $ticket->id,
            'status' => $status,
            'is_blocked' => $activeBlocker !== null,
            'blocker_reason' => $activeBlocker?->body,
            'owner_id' => $ticket->user_id2,
            'owner_label' => $ownerLabel,
            'next_action' => $latestAction ? [
                'id' => $latestAction->id,
                'body' => $latestAction->body,
                'created_at' => $latestAction->created_at,
            ] : null,
            'latest_decision' => $latestDecision ? [
                'id' => $latestDecision->id,
                'body' => $latestDecision->body,
                'created_at' => $latestDecision->created_at,
            ] : null,
            'execution_state' => $this->deriveExecutionState($ticket, $activeBlocker, $latestAction),
        ];
    }

    protected function deriveExecutionState(Ticket $ticket, ?Note $activeBlocker, ?Note $latestAction): string
    {
        if ($activeBlocker) {
            return 'BLOCKED';
        }

        if (! $latestAction) {
            return 'IDLE';
        }

        return 'ON TRACK';
    }
}
