<?php

namespace Tests\Unit\Models;

use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $type = new Type;

        $this->assertFalse($type->timestamps);
    }
}
