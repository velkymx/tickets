<?php

namespace Tests\Unit\Models;

use App\Models\Importance;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImportanceTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $importance = new Importance;

        $this->assertFalse($importance->timestamps);
    }
}
