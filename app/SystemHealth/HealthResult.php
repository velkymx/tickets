<?php

namespace App\SystemHealth;

readonly class HealthResult
{
    public function __construct(
        public HealthStatus $status,
        public string $label,
        public string $value = '',
        public ?string $fix = null,
    ) {}
}
