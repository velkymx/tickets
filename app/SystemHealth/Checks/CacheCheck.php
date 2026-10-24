<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Cache and session';
    }

    public function run(): array
    {
        $results = [$this->roundTrip()];

        if (config('cache.default') === 'redis') {
            $results[] = $this->redisPing();
        }

        return $results;
    }

    private function roundTrip(): HealthResult
    {
        try {
            Cache::put('health-check', 'ok', 60);
            $ok = Cache::get('health-check') === 'ok';
            Cache::forget('health-check');
        } catch (\Throwable) {
            $ok = false;
        }

        return $ok
            ? new HealthResult(HealthStatus::Ok, 'Cache read and write', config('cache.default').' (round trip ok)')
            : new HealthResult(
                HealthStatus::Problem,
                'Cache read and write',
                config('cache.default').' (write did not read back)',
                'Check the cache service and CACHE_STORE'
            );
    }

    private function redisPing(): HealthResult
    {
        try {
            $pong = Redis::connection()->ping();
        } catch (\Throwable $e) {
            return new HealthResult(
                HealthStatus::Problem,
                'Redis',
                'did not answer: '.$e->getMessage(),
                'Start redis or point REDIS_HOST at the right server'
            );
        }

        return new HealthResult(HealthStatus::Ok, 'Redis', 'answered ping');
    }
}
