<?php

namespace Tests\Unit\Models;

use App\Models\Importance;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
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

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $fillable = (new Importance)->getFillable();

        $this->assertContains('name', $fillable);
    }

    #[Test]
    public function it_has_many_tickets(): void
    {
        $importance = Importance::factory()->create();
        $ticket = Ticket::factory()->create(['importance_id' => $importance->id]);

        $this->assertInstanceOf(Collection::class, $importance->tickets);
        $this->assertTrue($importance->tickets->contains($ticket));
    }
}
