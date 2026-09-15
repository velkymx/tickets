<?php

namespace Tests\Unit\Services;

use App\Models\Ticket;
use App\Services\PriorityMatrixService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriorityMatrixServiceTest extends TestCase
{
    private PriorityMatrixService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PriorityMatrixService();
    }

    private function ticket(int $importanceId, float $estimate): Ticket
    {
        return (new Ticket())->forceFill([
            'importance_id' => $importanceId,
            'estimate' => $estimate,
        ]);
    }

    #[Test]
    public function high_impact_low_effort_is_a_quick_win(): void
    {
        $this->assertSame(
            PriorityMatrixService::QUICK_WINS,
            $this->service->quadrantFor($this->ticket(5, 3))
        );
    }

    #[Test]
    public function high_impact_high_effort_is_a_major_project(): void
    {
        $this->assertSame(
            PriorityMatrixService::MAJOR_PROJECTS,
            $this->service->quadrantFor($this->ticket(4, 13))
        );
    }

    #[Test]
    public function low_impact_low_effort_is_a_fill_in(): void
    {
        $this->assertSame(
            PriorityMatrixService::FILL_INS,
            $this->service->quadrantFor($this->ticket(2, 2))
        );
    }

    #[Test]
    public function low_impact_high_effort_is_thankless(): void
    {
        $this->assertSame(
            PriorityMatrixService::THANKLESS,
            $this->service->quadrantFor($this->ticket(1, 21))
        );
    }

    #[Test]
    public function major_importance_counts_as_low_impact(): void
    {
        // importance 3 (major) is below the critical/blocker cutoff.
        $this->assertSame(
            PriorityMatrixService::FILL_INS,
            $this->service->quadrantFor($this->ticket(3, 2))
        );
    }

    #[Test]
    public function zero_or_missing_estimate_is_unestimated(): void
    {
        $this->assertSame(
            PriorityMatrixService::UNESTIMATED,
            $this->service->quadrantFor($this->ticket(5, 0))
        );
    }

    #[Test]
    public function five_hours_is_low_effort_but_six_rounds_up_to_high(): void
    {
        // Fibonacci bands: 5h stays band 5 (low); 6h rounds up to band 8 (high).
        $this->assertSame(
            PriorityMatrixService::QUICK_WINS,
            $this->service->quadrantFor($this->ticket(5, 5))
        );
        $this->assertSame(
            PriorityMatrixService::MAJOR_PROJECTS,
            $this->service->quadrantFor($this->ticket(5, 6))
        );
    }

    #[Test]
    public function effort_band_rounds_up_to_nearest_fibonacci(): void
    {
        $this->assertSame(1, $this->service->effortBand(0.5));
        $this->assertSame(3, $this->service->effortBand(3));
        $this->assertSame(5, $this->service->effortBand(4));
        $this->assertSame(8, $this->service->effortBand(6));
        $this->assertSame(13, $this->service->effortBand(9));
    }

    #[Test]
    public function classify_groups_all_buckets_and_sorts_by_importance_desc(): void
    {
        $tickets = collect([
            $this->ticket(4, 2),  // quick win
            $this->ticket(5, 2),  // quick win (higher importance -> first)
            $this->ticket(5, 13), // major
            $this->ticket(2, 1),  // fill-in
            $this->ticket(1, 34), // thankless
            $this->ticket(5, 0),  // unestimated
        ]);

        $result = $this->service->classify($tickets);

        $this->assertCount(2, $result[PriorityMatrixService::QUICK_WINS]);
        $this->assertSame(5, $result[PriorityMatrixService::QUICK_WINS][0]->importance_id);
        $this->assertCount(1, $result[PriorityMatrixService::MAJOR_PROJECTS]);
        $this->assertCount(1, $result[PriorityMatrixService::FILL_INS]);
        $this->assertCount(1, $result[PriorityMatrixService::THANKLESS]);
        $this->assertCount(1, $result[PriorityMatrixService::UNESTIMATED]);
    }
}
