<?php

namespace Tests\Unit;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlashCommandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlashCommandServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlashCommandService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlashCommandService();
    }

    /** @test */
    public function it_can_change_status_via_slash_command()
    {
        $ticket = Ticket::factory()->create();
        $status = Status::factory()->create(['name' => 'Testing']);

        $this->service->handle($ticket, '/status Testing');

        $this->assertEquals($status->id, $ticket->fresh()->status_id);
    }

    /** @test */
    public function it_can_assign_user_via_slash_command()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create(['name' => 'JohnDoe']);

        $this->service->handle($ticket, '/assign @JohnDoe');

        $this->assertEquals($user->id, $ticket->fresh()->user_id2);
    }

    /** @test */
    public function it_can_close_ticket_via_slash_command()
    {
        $ticket = Ticket::factory()->create(['closed_at' => null]);

        $this->service->handle($ticket, '/close');

        $this->assertNotNull($ticket->fresh()->closed_at);
    }

    /** @test */
    public function it_can_log_hours_via_slash_command()
    {
        $ticket = Ticket::factory()->create();
        
        $results = $this->service->handle($ticket, '/hours 2.5');

        $this->assertEquals(2.5, $results['hours']);
    }

    /** @test */
    public function it_can_set_estimate_via_slash_command()
    {
        $ticket = Ticket::factory()->create(['estimate' => 0]);

        $this->service->handle($ticket, '/estimate 5');

        $this->assertEquals(5, $ticket->fresh()->estimate);
    }

    /** @test */
    public function it_extracts_signal_type_from_command()
    {
        $ticket = Ticket::factory()->create();
        
        $type = $this->service->getSignalType('/decision We use Redis');
        $this->assertEquals('decision', $type);

        $type = $this->service->getSignalType('/blocker Need API keys');
        $this->assertEquals('blocker', $type);
    }
}
