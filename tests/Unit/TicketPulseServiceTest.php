<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketPulseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TicketPulseServiceTest extends TestCase
{
    use RefreshDatabase;

    private TicketPulseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketPulseService();
    }

    /** @test */
    public function it_computes_and_caches_ticket_pulse()
    {
        $ticket = Ticket::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $pulse = $this->service->getPulse($ticket);

        $this->assertNotNull($pulse);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($pulse, Cache::get($cacheKey));
    }

    /** @test */
    public function it_invalidates_cache_on_demand()
    {
        $ticket = Ticket::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $this->service->invalidatePulse($ticket->id);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
