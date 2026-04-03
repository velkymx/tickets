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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketLifecycleTest extends TestCase
{
    use SeedsDatabase;

    protected User $user;

    protected User $user2;

    protected Status $openStatus;

    protected Status $closedStatus;

    protected Type $type;

    protected Importance $importance;

    protected Project $project;

    protected Milestone $milestone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->openStatus = Status::factory()->create(['name' => 'Open']);
        $this->closedStatus = Status::factory()->closed()->create();
        $this->type = Type::factory()->create();
        $this->importance = Importance::factory()->create();
        $this->project = Project::factory()->create();
        $this->milestone = Milestone::factory()->create();
    }

    #[Test]
    public function it_creates_and_updates_ticket(): void
    {
        $response = $this->actingAs($this->user)->post('/tickets', [
            'subject' => 'Test Ticket',
            'description' => 'Test Description',
            'type_id' => $this->type->id,
            'status_id' => $this->openStatus->id,
            'importance_id' => $this->importance->id,
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', ['subject' => 'Test Ticket']);

        $ticket = Ticket::first();
        $response = $this->actingAs($this->user)->put("/tickets/update/{$ticket->id}", [
            'type_id' => $this->type->id,
            'status_id' => $ticket->status_id,
            'importance_id' => $this->importance->id,
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function it_tracks_changes_in_changelog(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'user_id2' => $this->user->id,
            'status_id' => $this->openStatus->id,
        ]);

        $this->actingAs($this->user)->put("/tickets/update/{$ticket->id}", [
            'type_id' => $this->type->id,
            'status_id' => $ticket->status_id,
            'importance_id' => $this->importance->id,
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
        ]);

        $changelogNotes = Note::where('ticket_id', $ticket->id)
            ->where('notetype', 'changelog')
            ->count();

        $this->assertGreaterThanOrEqual(1, $changelogNotes);
    }

    #[Test]
    public function it_manages_watchers(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'user_id2' => $this->user2->id,
        ]);

        $this->actingAs($this->user2)->post("/tickets/watch/{$ticket->id}");
        $this->assertDatabaseHas('ticket_user_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user2->id,
        ]);

        $this->actingAs($this->user2)->post("/tickets/watch/{$ticket->id}");
        $this->assertDatabaseMissing('ticket_user_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user2->id,
        ]);
    }

    #[Test]
    public function it_creates_estimate(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'user_id2' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $this->assertDatabaseHas('ticket_estimates', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'storypoints' => 5,
        ]);
    }

    #[Test]
    public function it_clones_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Original Ticket',
            'description' => 'Original description',
            'type_id' => $this->type->id,
            'status_id' => $this->openStatus->id,
            'importance_id' => $this->importance->id,
            'project_id' => $this->project->id,
            'milestone_id' => $this->milestone->id,
        ]);

        $response = $this->actingAs($this->user)->get("/tickets/clone/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertViewHas('ticket');
    }
}
