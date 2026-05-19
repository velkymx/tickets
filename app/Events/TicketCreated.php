<?php

namespace App\Events;

use App\Models\Ticket;

class TicketCreated
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?int $actorId = null,
    ) {}
}
