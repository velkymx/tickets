<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class HealthPingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly string $token,
    ) {}

    public function retryUntil(): \DateTime
    {
        return now()->addSeconds(60)->toDateTime();
    }

    public function handle(): void
    {
        Cache::put("health-ping:{$this->token}", ['at' => microtime(true)], 120);

        if (Cache::get('health-ping-pending') === $this->token) {
            Cache::forget('health-ping-pending');
        }
    }
}
