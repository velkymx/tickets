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
    public function missing_driver_extension_is_reported(): void
    {
        $results = Requirements::check('pgsql');

        $names = array_column(array_map(fn ($r) => ['name' => $r->name, 'ok' => $r->ok], $results), 'ok', 'name');

        if (extension_loaded('pdo_pgsql')) {
            $this->assertTrue($names['PHP extension: pdo_pgsql']);
        } else {
            $this->assertFalse($names['PHP extension: pdo_pgsql']);
        }
    }

    #[Test]
    public function unknown_driver_reports_a_problem(): void
    {
        $results = Requirements::check('oracle');

        $this->assertNotEmpty(array_filter($results, fn ($r) => ! $r->ok));
    }
}
