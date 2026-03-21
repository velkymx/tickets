<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
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

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $fillable = (new Type)->getFillable();

        $this->assertContains('name', $fillable);
    }

    #[Test]
    public function it_has_many_tickets(): void
    {
        $type = Type::factory()->create();
        $ticket = Ticket::factory()->create(['type_id' => $type->id]);

        $this->assertInstanceOf(Collection::class, $type->tickets);
        $this->assertTrue($type->tickets->contains($ticket));
    }
}
