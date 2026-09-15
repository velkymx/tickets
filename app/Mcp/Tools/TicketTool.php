<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesApiUser;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class TicketTool extends Tool
{
    use ResolvesApiUser;

    /**
     * Find a ticket the user owns or is assigned to. Null when missing
     * or foreign, so callers return a clean not-found error either way.
     */
    protected function findTicket(User $user, int $id): ?Ticket
    {
        return Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->find($id);
    }

    protected function ticketNotFound(): Response
    {
        return Response::error('Ticket not found.');
    }

    protected function noteNotFound(): Response
    {
        return Response::error('Note not found on this ticket.');
    }
}
