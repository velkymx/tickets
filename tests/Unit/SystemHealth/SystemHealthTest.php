<?php

namespace Tests\Unit\SystemHealth;

use App\SystemHealth\Checks\RequirementsCheck;
use App\SystemHealth\Checks\SystemInfoCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use App\SystemHealth\SystemHealth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SystemHealthTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function report_collects_every_section(): void
    {
        $report = (new SystemHealth)->report();

        $this->assertArrayHasKey('System info', $report);
        $this->assertArrayHasKey('PHP requirements', $report);
        $this->assertNotEmpty($report['System info']);
        $this->assertNotEmpty($report['PHP requirements']);
    }

    #[Test]
    public function summary_counts_problems_and_warnings(): void
    {
        $health = new SystemHealth;

        $summary = $health->summary([
            'one' => [
                new HealthResult(HealthStatus::Problem, 'a'),
                new HealthResult(HealthStatus::Warning, 'b'),
                new HealthResult(HealthStatus::Ok, 'c'),
            ],
        ]);

        $this->assertSame(['problems' => 1, 'warnings' => 1], $summary);
    }

    #[Test]
    public function system_info_shows_versions(): void
    {
        $labels = array_column(array_map(fn ($r) => ['label' => $r->label], (new SystemInfoCheck)->run()), 'label');

        foreach (['Application', 'Environment', 'PHP', 'Laravel', 'OS', 'Database', 'Timezone'] as $label) {
            $this->assertContains($label, $labels);
        }
    }

    #[Test]
    public function requirements_pass_on_a_supported_install(): void
    {
        foreach ((new RequirementsCheck)->run() as $result) {
            $this->assertSame(HealthStatus::Ok, $result->status, $result->label);
        }
    }
}
