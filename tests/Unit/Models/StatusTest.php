<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class StatusTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $status = new Status;

        $this->assertFalse($status->timestamps);
    }

    #[Test]
    public function it_returns_closed_status_ids(): void
    {
        $closedIds = Status::closedStatusIds();

        sort($closedIds);

        $this->assertEquals([5, 8, 9], $closedIds);
    }

    #[Test]
    public function it_correctly_identifies_closed_status(): void
    {
        $this->assertTrue(Status::isClosed(5));
        $this->assertTrue(Status::isClosed(8));
        $this->assertTrue(Status::isClosed(9));
        $this->assertFalse(Status::isClosed(1));
        $this->assertFalse(Status::isClosed(2));
        $this->assertFalse(Status::isClosed(3));
        $this->assertFalse(Status::isClosed(6));
    }

    #[Test]
    public function it_returns_active_status_ids(): void
    {
        $activeIds = Status::activeStatusIds();

        sort($activeIds);

        $this->assertEquals([1, 2, 3, 4, 6, 7], $activeIds);
    }

    #[Test]
    public function it_uses_strict_comparison(): void
    {
        $this->assertFalse(Status::isClosed(0));
        $this->assertFalse(Status::isClosed(99));
    }
}
