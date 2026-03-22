<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Status;
use App\Services\TicketPulseService;
use App\ValueObjects\TicketPulse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function it_computes_and_caches_ticket_pulse()
    {
        $ticket = Ticket::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $pulse = $this->service->getPulse($ticket);

        $this->assertInstanceOf(TicketPulse::class, $pulse);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($pulse, Cache::get($cacheKey));
    }

    #[Test]
    public function it_invalidates_cache_on_demand()
    {
        $ticket = Ticket::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $this->service->invalidatePulse($ticket->id);
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_shows_you_own_this_for_the_current_assignee()
    {
        $user = User::factory()->create(['name' => 'Sarah']);
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $this->actingAs($user);

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('You own this', $pulse->owner_label);
    }

    #[Test]
    public function it_extracts_waiting_on_from_the_latest_blocker_mention()
    {
        $owner = User::factory()->create(['name' => 'Owner']);
        $blockedOn = User::factory()->create(['name' => 'mike']);
        $ticket = Ticket::factory()->create(['user_id2' => $owner->id]);

        Note::create([
            'body' => 'Waiting on @mike for API keys',
            'user_id' => $owner->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('Waiting on: @mike', $pulse->owner_label);
    }

    #[Test]
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

    #[Test]
    public function it_exposes_a_muted_next_action_when_no_action_exists()
    {
        $ticket = Ticket::factory()->create();

        $pulse = $this->service->getPulse($ticket);

        $this->assertSame('No next action defined', $pulse->next_action['body']);
        $this->assertNull($pulse->next_action['assignee']);
    }

    #[Test]
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
        $this->assertEquals($user->name, $pulse->latest_decision['author']);
        $this->assertEquals('Old decision', $pulse->latest_decision['supersedes']);
    }

    #[Test]
    public function it_surfaces_open_threads_and_latest_blocker_details()
    {
        $user = User::factory()->create(['name' => 'Sarah']);
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);
        $thread = Note::create([
            'body' => "Race condition discussion\nMore detail below",
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'message',
            'resolved' => false,
        ]);
        Note::create([
            'body' => 'First reply',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
            'notetype' => 'message',
        ]);
        Note::create([
            'body' => 'Second reply',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $thread->id,
            'notetype' => 'message',
        ]);
        $blocker = Note::create([
            'body' => 'Waiting on API team',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'blocker',
            'resolved' => false,
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertCount(1, $pulse->open_threads);
        $this->assertEquals('Race condition discussion', $pulse->open_threads[0]['subject']);
        $this->assertEquals(2, $pulse->open_threads[0]['reply_count']);
        $this->assertEquals($blocker->id, $pulse->latest_blocker['id']);
        $this->assertEquals('Sarah', $pulse->latest_blocker['author']);
    }

    #[Test]
    public function it_derives_execution_state_and_staleness_from_recent_activity()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        $note = Note::create([
            'body' => 'Old update',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'update',
        ]);
        $note->timestamps = false;
        $note->created_at = now()->subDays(3);
        $note->updated_at = now()->subDays(3);
        $note->save();

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('AT RISK', $pulse->execution_state);
        $this->assertTrue($pulse->is_stale);
        $this->assertStringContainsString('No updates in', $pulse->staleness_message);
    }

    #[Test]
    public function it_reports_on_track_when_there_is_recent_activity_and_a_next_action()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();
        Note::create([
            'body' => 'Handle QA verification',
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'notetype' => 'action',
            'resolved' => false,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('ON TRACK', $pulse->execution_state);
        $this->assertFalse($pulse->is_stale);
    }

    #[Test]
    public function it_reports_idle_when_there_is_no_activity_or_action()
    {
        $ticket = Ticket::factory()->create();

        $pulse = $this->service->getPulse($ticket);

        $this->assertEquals('IDLE', $pulse->execution_state);
        $this->assertNull($pulse->last_activity_at);
    }

    #[Test]
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

    #[Test]
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
