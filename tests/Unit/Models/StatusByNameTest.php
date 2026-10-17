<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class StatusByNameTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function by_name_matches_case_insensitively(): void
    {
        $status = Status::factory()->create(['name' => 'Closed']);

        $this->assertTrue(Status::byName('closed')->whereKey($status->id)->exists());
        $this->assertTrue(Status::byName('CLOSED')->whereKey($status->id)->exists());
        $this->assertTrue(Status::byName('Closed')->whereKey($status->id)->exists());
    }

    #[Test]
    public function by_name_returns_empty_for_unknown_names(): void
    {
        Status::factory()->create(['name' => 'Closed']);

        $this->assertFalse(Status::byName('nope')->exists());
    }
}
