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
        $entry = $this->normalize($user, $notification);

        // In-app delivery is never gated; only mail is.
        $user->notifyNow($notification, ['database']);

        if (! $user->mailsFor($this->prefKey($entry['type']))) {
            return;
        }

        if ($user->notificationPrefs()['email_mode'] === 'immediate') {
            $user->notifyNow($notification, ['mail']);

            return;
        }

        $batchKey = $this->batchKey($user->id, $ticketId);
        $scheduleKey = $this->scheduleKey($user->id, $ticketId);

        $lock = Cache::lock("{$batchKey}:lock", 5);

        try {
            $lock->block(2);
            $entries = Cache::get($batchKey, []);
            $entries[] = $entry;
            Cache::put($batchKey, $entries, now()->addMinutes(10));
        } finally {
            $lock->release();
        }

        if (Cache::add($scheduleKey, true, now()->addMinutes(10))) {
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

    private function prefKey(string $type): string
    {
        return match ($type) {
            'mention' => 'mentions',
            'reply' => 'replies',
            'watching' => 'watching',
            default => throw new \InvalidArgumentException("Unknown notification type [{$type}]."),
        };
    }

    private function normalize(User $user, Notification $notification): array
    {
        $data = method_exists($notification, 'toArray') ? $notification->toArray($user) : [];

        // Fail at write time, not in the digest job against stale cache:
        // every batched notification must declare its preference type.
        $type = $data['type'] ?? throw new \InvalidArgumentException(
            $notification::class.'::toArray() must include a "type" key.'
        );

        return [
            'type' => $type,
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
