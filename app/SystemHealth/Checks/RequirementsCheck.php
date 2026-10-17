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
        $results = [];

        foreach (Requirements::check((string) config('database.default')) as $requirement) {
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
