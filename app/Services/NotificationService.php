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
        $activeViewerIds = $this->activeViewerIds($ticket, $watchers->pluck('user_id'));
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

            if ($watcher->muted || in_array($user->id, $activeViewerIds, true)) {
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
        $activeViewerIds = $this->activeViewerIds($ticket, $watchers->pluck('user_id'));
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

            if ($watcher->muted || in_array($user->id, $activeViewerIds, true)) {
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

    private function activeViewerIds(Ticket $ticket, $watcherUserIds): array
    {
        return TicketView::query()
            ->where('ticket_id', $ticket->id)
            ->whereIn('user_id', $watcherUserIds)
            ->where('updated_at', '>=', now()->subMinutes(2))
            ->pluck('user_id')
            ->all();
    }
}
