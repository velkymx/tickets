<?php

namespace Tests\Feature\Observers;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketPulseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PulseCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private TicketPulseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TicketPulseService::class);
    }

    #[Test]
    public function hiding_a_note_invalidates_the_ticket_pulse_cache(): void
    {
        [$ticket, $note] = $this->makeTicketAndNote();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $note->hide = true;
        $note->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function resolving_a_note_invalidates_the_ticket_pulse_cache(): void
    {
        [$ticket, $note] = $this->makeTicketAndNote(['notetype' => 'blocker', 'resolved' => false]);
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $note->resolved = true;
        $note->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function changing_ticket_status_invalidates_the_ticket_pulse_cache(): void
    {
        $ticket = Ticket::factory()->create();
        $status = Status::factory()->create(['name' => 'Testing']);
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $ticket->status_id = $status->id;
        $ticket->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function changing_ticket_assignee_invalidates_the_ticket_pulse_cache(): void
    {
        $ticket = Ticket::factory()->create();
        $newAssignee = User::factory()->create();
        $cacheKey = "ticket_pulse:{$ticket->id}";

        $this->service->getPulse($ticket);
        $this->assertTrue(Cache::has($cacheKey));

        $ticket->user_id2 = $newAssignee->id;
        $ticket->save();

        $this->assertFalse(Cache::has($cacheKey));
    }

    private function makeTicketAndNote(array $overrides = []): array
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();
        $note = Note::factory()->create(array_merge([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ], $overrides));

        return [$ticket, $note];
    }
}
