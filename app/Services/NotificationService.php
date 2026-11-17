<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\TicketView;
use App\Notifications\WatcherDatabaseNotification;
use App\Notifications\WatcherNotification;

class NotificationService
{
    public function __construct(
        private ?NotificationBatchService $batchService = null,
    ) {
        $this->batchService ??= app(NotificationBatchService::class);
    }

    public function notifyWatchers(Ticket $ticket, Note $note): void
    {
        $watchers = $ticket->watchers()->with('user')->get();
        $mentionedUserIds = $note->mentions()->pluck('user_id')->toArray();
        $notifiedUserIds = [];

        foreach ($watchers as $watcher) {
            $user = $watcher->user;

            if (! $user || $user->id === $note->user_id) {
                continue;
            }

            if (in_array($user->id, $mentionedUserIds)) {
                continue;
            }

            if (in_array($user->id, $notifiedUserIds)) {
                continue;
            }

            $notifiedUserIds[] = $user->id;

            $message = "New activity on Ticket #{$ticket->id}: {$ticket->subject}";
            $url = url("/tickets/{$ticket->id}");

            if ($watcher->muted || $this->isActiveViewer($user->id, $ticket->id)) {
                $this->batchService->dispatch(
                    $user,
                    new WatcherDatabaseNotification('Ticket', $message, $url),
                    $ticket->id
                );

                continue;
            }

            $this->batchService->dispatch(
                $user,
                new WatcherNotification('Ticket', $message, $url),
                $ticket->id
            );
        }
    }

    public function notifyTicketUpdate(Ticket $ticket, ?int $exceptUserId = null): void
    {
        $watchers = $ticket->watchers()->with('user')->get();
        $notifiedUserIds = [];

        foreach ($watchers as $watcher) {
            $user = $watcher->user;

            if (! $user || $user->id === $exceptUserId) {
                continue;
            }

            if (in_array($user->id, $notifiedUserIds)) {
                continue;
            }

            $notifiedUserIds[] = $user->id;

            $message = "The Ticket '{$ticket->subject}' has been updated.";
            $url = url("/tickets/{$ticket->id}");

            if ($watcher->muted || $this->isActiveViewer($user->id, $ticket->id)) {
                $this->batchService->dispatch(
                    $user,
                    new WatcherDatabaseNotification('Ticket', $message, $url),
                    $ticket->id
                );

                continue;
            }

            $this->batchService->dispatch(
                $user,
                new WatcherNotification('Ticket', $message, $url),
                $ticket->id
            );
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
