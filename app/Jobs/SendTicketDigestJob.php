<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\TicketDigestNotification;
use App\Services\NotificationBatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SendTicketDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $ticketId,
    ) {}

    public function handle(NotificationBatchService $batchService): void
    {
        $batchKey = $batchService->batchKey($this->userId, $this->ticketId);
        $scheduleKey = $batchService->scheduleKey($this->userId, $this->ticketId);

        $entries = Cache::get($batchKey, []);
        $user = User::query()->find($this->userId);

        if ($user && $entries !== []) {
            $user->notifyNow(new TicketDigestNotification($this->ticketId, $entries), ['mail']);
        }

        Cache::forget($batchKey);
        Cache::forget($scheduleKey);
    }
}
