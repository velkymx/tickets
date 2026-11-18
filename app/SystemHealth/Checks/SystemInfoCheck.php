<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;

class SystemInfoCheck implements HealthCheck
{
    public function section(): string
    {
        return 'System info';
    }

    public function run(): array
    {
        return [
            new HealthResult(HealthStatus::Ok, 'Application', config('app.name').' '.config('app.version')),
            new HealthResult(HealthStatus::Ok, 'Environment', (string) config('app.env')),
            new HealthResult(HealthStatus::Ok, 'PHP', PHP_VERSION.' ('.PHP_SAPI.')'),
            new HealthResult(HealthStatus::Ok, 'Laravel', Application::VERSION),
            new HealthResult(HealthStatus::Ok, 'OS', php_uname('s').' '.php_uname('r')),
            new HealthResult(HealthStatus::Ok, 'Database', $this->database()),
            new HealthResult(HealthStatus::Ok, 'Timezone', (string) config('app.timezone')),
            new HealthResult(HealthStatus::Ok, 'memory_limit', (string) ini_get('memory_limit')),
            new HealthResult(HealthStatus::Ok, 'upload_max_filesize', (string) ini_get('upload_max_filesize')),
            new HealthResult(HealthStatus::Ok, 'post_max_size', (string) ini_get('post_max_size')),
        ];
    }

    private function database(): string
    {
        try {
            $connection = DB::connection();

            return $connection->getDriverName().' ('.$connection->getServerVersion().')';
        } catch (\Throwable) {
            return (string) config('database.default').' (unreachable)';
        }
    }
}
