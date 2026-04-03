<?php

namespace App\Services;

use App\Models\TicketView;
use App\Models\User;

class PresenceService
{
    protected const TTL = 30; // Seconds before expiration

    public function updatePresence(int $ticketId, User $user): void
    {
        $view = TicketView::firstOrCreate([
            'ticket_id' => $ticketId,
            'user_id' => $user->id,
        ]);

        $view->touch();
    }

    public function getViewers(int $ticketId): array
    {
        return TicketView::query()
            ->with('user')
            ->where('ticket_id', $ticketId)
            ->where('updated_at', '>', now()->subSeconds(self::TTL))
            ->orderByDesc('updated_at')
            ->get()
            ->filter(fn (TicketView $view) => $view->user !== null)
            ->map(fn (TicketView $view) => [
                'user_id' => $view->user_id,
                'name' => $view->user->name,
                'avatar_url' => $view->user->avatarUrl(),
                'last_seen' => $view->updated_at->timestamp,
            ])
            ->values()
            ->all();
    }
}
