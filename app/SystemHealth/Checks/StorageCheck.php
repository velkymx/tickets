<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;

class StorageCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Storage';
    }

    public function run(): array
    {
        return [
            $this->writable('storage/', storage_path()),
            $this->writable('bootstrap/cache', base_path('bootstrap/cache')),
            $this->publicLink(),
            new HealthResult(HealthStatus::Ok, 'Free disk space', $this->freeSpace()),
        ];
    }

    private function writable(string $label, string $path): HealthResult
    {
        return is_writable($path)
            ? new HealthResult(HealthStatus::Ok, $label, 'writable')
            : new HealthResult(
                HealthStatus::Problem,
                $label,
                'not writable',
                "Make {$path} writable by the web server user"
            );
    }

    private function publicLink(): HealthResult
    {
        return is_link(public_path('storage'))
            ? new HealthResult(HealthStatus::Ok, 'public/storage link', 'present')
            : new HealthResult(
                HealthStatus::Problem,
                'public/storage link',
                'missing (avatars and attachments break)',
                'Run php artisan storage:link'
            );
    }

    private function freeSpace(): string
    {
        $free = disk_free_space(storage_path());

        if ($free === false) {
            return 'unknown';
        }

        return number_format($free / 1024 / 1024 / 1024, 1).' GB free';
    }
}
