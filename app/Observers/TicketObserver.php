<?php

namespace App\Observers;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Services\TicketPulseService;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
        event(new TicketCreated($ticket, auth()->id()));
    }

    public function updated(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
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
