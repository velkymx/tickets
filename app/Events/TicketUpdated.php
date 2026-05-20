<?php

namespace App\Events;

use App\Models\Ticket;

class TicketUpdated
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly array $changes = [],
        public readonly ?int $actorId = null,
    ) {}
}
