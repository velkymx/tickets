<?php

namespace App\Observers;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Status;
use App\Models\Ticket;
use App\Services\TicketPulseService;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
        event(new TicketCreated($ticket, auth()->id()));
    }

    public function updating(Ticket $ticket): void
    {
        if (! $ticket->isDirty('status_id')) {
            return;
        }

        $wasClosed = Status::isClosed((int) $ticket->getOriginal('status_id'));
        $isClosed = Status::isClosed((int) $ticket->status_id);

        // Auto-manage closed_at on status transitions, unless explicitly set.
        if ($isClosed && ! $wasClosed && ! $ticket->isDirty('closed_at')) {
            $ticket->closed_at = now();
        }

        if ($wasClosed && ! $isClosed && ! $ticket->isDirty('closed_at')) {
            $ticket->closed_at = null;
        }
    }

    public function updated(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);

        if ($ticket->wasChanged('status_id')) {
            $wasClosed = Status::isClosed((int) $ticket->getOriginal('status_id'));
            $isClosed = Status::isClosed((int) $ticket->status_id);

            if ($wasClosed && ! $isClosed) {
                $ticket->recordReopenAuditNote();
            }
        }

        if ($ticket->wasChanged(['subject', 'description', 'status_id', 'user_id2', 'milestone_id', 'project_id', 'importance_id', 'due_at', 'closed_at', 'estimate', 'storypoints', 'actual'])) {
            $ticket->notifyWatchers('Ticket', auth()->id() ?? 0);
        }

        event(new TicketUpdated($ticket, $ticket->getChanges(), auth()->id()));
    }

    public function deleted(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
    }

    private function invalidatePulse(Ticket $ticket): void
    {
        app(TicketPulseService::class)->invalidatePulse($ticket->id);
    }
}
