<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketView;
use App\Notifications\WatcherDatabaseNotification;
use App\Notifications\WatcherNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyWatchers(Ticket $ticket, Note $note): void
    {
        $watchers = $ticket->watchers()->with('user')->get();
        $mentionedUserIds = $note->mentions()->pluck('user_id')->toArray();
        $notifiedUserIds = [];

        foreach ($watchers as $watcher) {
            $user = $watcher->user;

            // Don't notify the comment author about their own comment
            if ($user->id === $note->user_id) {
                continue;
            }

            // Skip if already notified (deduplication for mentioned watchers)
            if (in_array($user->id, $notifiedUserIds)) {
                continue;
            }

            $notifiedUserIds[] = $user->id;

            $message = "New activity on Ticket #{$ticket->id}: {$ticket->subject}";
            $url = url("tickets/{$ticket->id}");

            // Muted watchers or active viewers get database-only notification
            if ($watcher->muted || $this->isActiveViewer($user->id, $ticket->id)) {
                $user->notify(new WatcherDatabaseNotification('Ticket', $message, $url));
                continue;
            }

            $user->notify(new WatcherNotification('Ticket', $message, $url));
        }
    }

    private function isActiveViewer(int $userId, int $ticketId): bool
    {
        $view = TicketView::where('user_id', $userId)
            ->where('ticket_id', $ticketId)
            ->first();

        if (! $view) {
            return false;
        }

        return $view->updated_at->diffInMinutes(now()) < 2;
    }
}
