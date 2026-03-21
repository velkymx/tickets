<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $status = new Status;

        $this->assertFalse($status->timestamps);
    }

    #[Test]
    public function it_returns_closed_status_ids(): void
    {
        $this->assertEquals([5, 8, 9], Status::closedStatusIds());
    }

    #[Test]
    public function it_correctly_identifies_closed_status(): void
    {
        $this->assertTrue(Status::isClosed(5));
        $this->assertTrue(Status::isClosed(8));
        $this->assertTrue(Status::isClosed(9));
        $this->assertFalse(Status::isClosed(1));
        $this->assertFalse(Status::isClosed(2));
    }

    #[Test]
    public function it_uses_strict_comparison(): void
    {
        $this->assertFalse(Status::isClosed(0));
        $this->assertFalse(Status::isClosed(99));
    }
}
