<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed statuses matching the default_lookup_values seeder
        Status::insert([
            ['id' => 1, 'name' => 'new'],
            ['id' => 2, 'name' => 'active'],
            ['id' => 3, 'name' => 'testing'],
            ['id' => 4, 'name' => 'ready to deploy'],
            ['id' => 5, 'name' => 'completed'],
            ['id' => 6, 'name' => 'waiting'],
            ['id' => 7, 'name' => 'reopened'],
            ['id' => 8, 'name' => 'duplicte'],
            ['id' => 9, 'name' => 'declined'],
        ]);
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
