<?php

namespace Tests\Unit\Models;

use App\Models\Importance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $importance = new Importance;

        $this->assertFalse($importance->timestamps);
    }
}
