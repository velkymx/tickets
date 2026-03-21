<?php

namespace Tests\Feature\Integration;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MilestoneLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Status $openStatus;

    protected Status $closedStatus;

    protected Type $type;

    protected Importance $importance;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->openStatus = Status::factory()->create(['name' => 'Open']);
        $this->closedStatus = Status::factory()->create(['id' => 5]);
        $this->type = Type::factory()->create();
        $this->importance = Importance::factory()->create();
        $this->project = Project::factory()->create();
    }

    #[Test]
    public function it_creates_milestone_adds_tickets_generates_report(): void
    {
        $milestone = Milestone::factory()->create([
            'name' => 'Sprint 1',
        ]);

        $ticket1 = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->openStatus->id,
            'storypoints' => 5,
        ]);

        $ticket2 = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->closedStatus->id,
            'storypoints' => 3,
        ]);

        $response = $this->actingAs($this->user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('totalTickets', 2);
        $response->assertViewHas('completedTickets', 1);
        $response->assertViewHas('openTickets', 1);
        $response->assertViewHas('totalStoryPoints', 8);
        $response->assertViewHas('completedStoryPoints', 3);
        $response->assertViewHas('remainingStoryPoints', 5);
    }

    #[Test]
    public function it_calculates_burndown_correctly(): void
    {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-10');

        $milestone = Milestone::factory()->create([
            'name' => 'Sprint 1',
            'start_at' => $startDate->format('Y-m-d'),
            'due_at' => $endDate->format('Y-m-d'),
        ]);

        $ticket1 = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->closedStatus->id,
            'storypoints' => 5,
            'closed_at' => '2024-01-05 10:00:00',
        ]);

        $ticket2 = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->openStatus->id,
            'storypoints' => 3,
        ]);

        $response = $this->actingAs($this->user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('burndownData');
        $response->assertViewHas('duration', 9);

        $burndownData = $response->viewData('burndownData');
        $this->assertArrayHasKey('labels', $burndownData);
        $this->assertArrayHasKey('ideal', $burndownData);
        $this->assertArrayHasKey('actual', $burndownData);
    }

    #[Test]
    public function it_handles_milestone_with_no_tickets(): void
    {
        $milestone = Milestone::factory()->create([
            'name' => 'Empty Sprint',
        ]);

        $response = $this->actingAs($this->user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 0);

        $response = $this->actingAs($this->user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('totalTickets', 0);
        $response->assertViewHas('burndownData', []);
    }

    #[Test]
    public function it_calculates_completion_percentage_correctly(): void
    {
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->openStatus->id,
        ]);
        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $this->closedStatus->id,
        ]);

        $response = $this->actingAs($this->user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 50);
    }

    #[Test]
    public function it_tracks_team_hours_from_notes(): void
    {
        $milestone = Milestone::factory()->create();
        $ticket = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
        ]);

        Note::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Worked on this',
            'hours' => 5.5,
            'notetype' => 'message',
        ]);

        $response = $this->actingAs($this->user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('teamHours');
    }
}
