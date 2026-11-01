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
    public function every_driver_maps_to_its_pdo_extension(): void
    {
        foreach (Requirements::DRIVER_EXTENSIONS as $driver => $ext) {
            $names = array_map(fn ($r) => $r->name, Requirements::check($driver));

            $this->assertContains("PHP extension: {$ext}", $names);
        }
    }

    #[Test]
    public function unknown_driver_reports_a_problem(): void
    {
        $results = Requirements::check('oracle');

        $this->assertNotEmpty(array_filter($results, fn ($r) => ! $r->ok));
    }
}
