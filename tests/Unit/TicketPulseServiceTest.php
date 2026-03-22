<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketPulseService;
use App\ValueObjects\TicketPulse;
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

        $this->assertInstanceOf(TicketPulse::class, $pulse);
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

    /** @test */
    public function it_shows_blocked_status_when_active_blocker_exists()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        
        Note::create([
            'body' => 'Waiting on API',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertTrue($pulse->is_blocked);
        $this->assertEquals('Waiting on API', $pulse->blocker_reason);
        $this->assertEquals('BLOCKED', $pulse->status);
    }

    /** @test */
    public function it_shows_correct_ownership_labels()
    {
        $user = User::factory()->create(['name' => 'Sarah']);
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $pulse = $this->service->getPulse($ticket);
        $this->assertEquals('Owner: Sarah', $pulse->owner_label);

        // Unassigned
        $unassignedTicket = Ticket::factory()->create(['user_id2' => 999999]);
        $this->service->invalidatePulse($unassignedTicket->id);
        $pulse = $this->service->getPulse($unassignedTicket);
        $this->assertEquals('Unassigned', $pulse->owner_label);
    }

    /** @test */
    public function it_surfaces_the_latest_action()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        Note::create([
            'body' => 'Old action',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
            'resolved' => false,
            'created_at' => now()->subDay(),
        ]);

        $latestAction = Note::create([
            'body' => 'Latest action',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
            'resolved' => false,
            'created_at' => now(),
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('Latest action', $pulse->next_action['body']);
    }

    /** @test */
    public function it_surfaces_the_latest_non_superseded_decision()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $oldDecision = Note::create([
            'body' => 'Old decision',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
        ]);

        $latestDecision = Note::create([
            'body' => 'Latest decision',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'decision',
            'supersedes_id' => $oldDecision->id,
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('Latest decision', $pulse->latest_decision['body']);
    }

    /** @test */
    public function it_invalidates_cached_pulse_when_a_ticket_changes()
    {
        $ticket = Ticket::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $ticket->subject = 'Updated subject';
        $ticket->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_invalidates_cached_pulse_when_a_note_changes()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        Note::create([
            'body' => 'New activity',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
        ]);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
