<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;

class MailCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Mail';
    }

    public function run(): array
    {
        $mailer = (string) config('mail.default');

        if (! in_array($mailer, ['log', 'array'], true)) {
            return [new HealthResult(HealthStatus::Ok, 'Mailer', "{$mailer} (sends mail)")];
        }

        if (config('app.env') === 'local') {
            return [new HealthResult(HealthStatus::Ok, 'Mailer', "{$mailer} (local development)")];
        }

        return [new HealthResult(
            HealthStatus::Warning,
            'Mailer',
            "{$mailer} (notifications go nowhere)",
            'Set MAIL_MAILER to smtp or another real mailer'
        )];
    }
}
