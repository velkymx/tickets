<?php

namespace App\SystemHealth;

interface HealthCheck
{
    public function section(): string;

    /**
     * @return list<HealthResult>
     */
    public function run(): array;
}
