<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            || $user->id === $ticket->user_id2;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            || $user->id === $ticket->user_id2;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function estimate(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function addNote(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            || $user->id === $ticket->user_id2;
    }
}
