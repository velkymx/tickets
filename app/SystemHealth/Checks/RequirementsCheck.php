<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use App\SystemHealth\Requirements;

class RequirementsCheck implements HealthCheck
{
    public function section(): string
    {
        return 'PHP requirements';
    }

    public function run(): array
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver", $connection);

        $results = [];

        foreach (Requirements::check($driver) as $requirement) {
            $results[] = new HealthResult(
                $requirement->ok ? HealthStatus::Ok : HealthStatus::Problem,
                $requirement->name,
                $requirement->detail,
                $requirement->ok ? null : $this->fix($requirement->name)
            );
        }

        return $results;
    }

    private function fix(string $name): string
    {
        if (str_starts_with($name, 'PHP extension')) {
            return 'Install the missing PHP extension and restart PHP';
        }

        return 'Upgrade PHP to 8.4 or newer';
    }
}
