<?php

namespace App\SystemHealth;

enum HealthStatus: string
{
    case Ok = 'OK';
    case Warning = 'Warning';
    case Problem = 'Problem';
    case Enabled = 'Enabled';
    case Disabled = 'Disabled';
    case Error = 'Error';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ok, self::Enabled => 'text-bg-success',
            self::Disabled => 'text-bg-secondary',
            self::Warning => 'text-bg-warning',
            self::Problem, self::Error => 'text-bg-danger',
        };
    }
}
