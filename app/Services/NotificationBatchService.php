<?php

namespace App\Services;

use App\Jobs\SendTicketDigestJob;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class NotificationBatchService
{
    public function dispatch(User $user, Notification $notification, int $ticketId): void
    {
        $user->notifyNow($notification, ['database']);

        $batchKey = $this->batchKey($user->id, $ticketId);
        $scheduleKey = $this->scheduleKey($user->id, $ticketId);

        $entries = Cache::get($batchKey, []);
        $entries[] = $this->normalize($user, $notification);

        Cache::put($batchKey, $entries, now()->addMinutes(10));

        if (! Cache::has($scheduleKey)) {
            Cache::put($scheduleKey, true, now()->addMinutes(10));
            SendTicketDigestJob::dispatch($user->id, $ticketId)->delay(now()->addMinutes(5));
        }
    }

    public function batchKey(int $userId, int $ticketId): string
    {
        return "ticket-notification-batch:{$userId}:{$ticketId}";
    }

    public function scheduleKey(int $userId, int $ticketId): string
    {
        return "ticket-notification-batch-scheduled:{$userId}:{$ticketId}";
    }

    private function normalize(User $user, Notification $notification): array
    {
        $data = method_exists($notification, 'toArray') ? $notification->toArray($user) : [];

        return [
            'type' => $data['type'] ?? 'activity',
            'subject' => $this->subjectFor($data),
            'excerpt' => $data['excerpt'] ?? $data['message'] ?? '',
            'url' => $data['url'] ?? '',
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function subjectFor(array $data): string
    {
        return match ($data['type'] ?? null) {
            'mention' => "{$data['actor_name']} mentioned you in Ticket #{$data['ticket_id']}",
            'reply' => "{$data['actor_name']} replied to your comment in Ticket #{$data['ticket_id']}",
            'watching' => $data['message'] ?? 'Ticket updated',
            default => 'Ticket activity',
        };
    }
}
