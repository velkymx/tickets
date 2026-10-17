<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;

class SecurityCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Security and config';
    }

    public function run(): array
    {
        $results = [];

        if ((bool) config('app.debug') && config('app.env') !== 'local') {
            $results[] = new HealthResult(
                HealthStatus::Problem,
                'Debug mode',
                'APP_DEBUG=true outside local',
                'Set APP_DEBUG=false in production'
            );
        } else {
            $results[] = new HealthResult(
                HealthStatus::Ok,
                'Debug mode',
                config('app.debug') ? 'on (local)' : 'off'
            );
        }

        $results[] = config('app.key') === '' || config('app.key') === null
            ? new HealthResult(
                HealthStatus::Problem,
                'App key',
                'APP_KEY is empty',
                'Run php artisan key:generate'
            )
            : new HealthResult(HealthStatus::Ok, 'App key', 'set');

        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = (string) request()->getHost();

        $results[] = $appHost !== '' && $appHost !== $requestHost
            ? new HealthResult(
                HealthStatus::Warning,
                'App URL',
                'APP_URL host differs from this request',
                'Set APP_URL to the public host so email links work'
            )
            : new HealthResult(HealthStatus::Ok, 'App URL', (string) config('app.url'));

        if (config('app.env') === 'production' && ! app()->configurationIsCached()) {
            $results[] = new HealthResult(
                HealthStatus::Warning,
                'Config cache',
                'not cached in production',
                'Run php artisan config:cache'
            );
        }

        return $results;
    }
}
