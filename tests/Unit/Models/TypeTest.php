<?php

namespace Tests\Unit\Models;

use App\Models\Type;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TypeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $type = new Type;

        $this->assertFalse($type->timestamps);
    }
}
