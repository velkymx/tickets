<?php

namespace Tests\Unit\SystemHealth;

use App\SystemHealth\Requirements;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequirementsTest extends TestCase
{
    #[Test]
    public function current_environment_passes_all_base_requirements(): void
    {
        $results = Requirements::check();

        $this->assertNotEmpty($results);

        foreach ($results as $result) {
            $this->assertTrue($result->ok, "{$result->name}: {$result->detail}");
        }
    }

    #[Test]
    public function named_driver_reports_its_pdo_extension(): void
    {
        $names = array_map(fn ($r) => $r->name, Requirements::check('pgsql'));

        $this->assertContains('PHP extension: pdo_pgsql', $names);
    }

    #[Test]
    public function unknown_driver_reports_a_problem(): void
    {
        $results = Requirements::check('oracle');

        $this->assertNotEmpty(array_filter($results, fn ($r) => ! $r->ok));
    }
}
