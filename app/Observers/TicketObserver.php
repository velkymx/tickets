<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\TicketPulseService;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
    }

    public function updated(Ticket $ticket): void
    {
        $this->invalidatePulse($ticket);
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
