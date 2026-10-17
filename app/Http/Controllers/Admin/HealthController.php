<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\HealthPingJob;
use App\SystemHealth\SystemHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class HealthController extends Controller
{
    public function show(SystemHealth $health)
    {
        $report = $health->report();

        return view('admin.health', [
            'report' => $report,
            'summary' => $health->summary($report),
            'sharedCache' => config('cache.default') !== 'array',
            'queueConnection' => (string) config('queue.default'),
        ]);
    }

    public function ping()
    {
        $lock = Cache::lock('health-ping-lock', 30);

        if (! $lock->get()) {
            return response()->json(['token' => Cache::get('health-ping-pending'), 'pending' => true]);
        }

        try {
            if ($pending = Cache::get('health-ping-pending')) {
                return response()->json(['token' => $pending, 'pending' => true]);
            }

            $token = Str::random(32);
            Cache::put('health-ping-pending', $token, 30);
            Cache::put("health-ping-sent:{$token}", now()->timestamp, 30);

            try {
                HealthPingJob::dispatch($token);
            } catch (\Throwable $e) {
                Cache::forget('health-ping-pending');

                return response()->json(['error' => $e->getMessage()], 500);
            }

            return response()->json(['token' => $token, 'pending' => true]);
        } finally {
            $lock->release();
        }
    }

    public function pingStatus(string $token)
    {
        $ran = Cache::get("health-ping:{$token}");

        if (! $ran) {
            return response()->json(['status' => 'pending']);
        }

        $sent = Cache::get("health-ping-sent:{$token}", $ran['at']);

        return response()->json([
            'status' => 'answered',
            'duration' => max(0, $ran['at'] - $sent),
        ]);
    }
}
