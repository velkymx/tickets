<?php

namespace App\SystemHealth;

readonly class RequirementResult
{
    public function __construct(
        public string $name,
        public bool $ok,
        public string $detail,
    ) {}
}
