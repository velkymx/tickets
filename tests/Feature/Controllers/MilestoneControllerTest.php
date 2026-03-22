<?php

namespace Tests\Feature\Controllers;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MilestoneControllerTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/milestone');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_returns_milestones_ordered_by_name(): void
    {
        $user = User::factory()->create();
        Milestone::factory()->create(['name' => 'Zebra']);
        Milestone::factory()->create(['name' => 'Apple']);

        $response = $this->actingAs($user)->get('/milestone');

        $response->assertStatus(200);
        $this->assertEquals(['Apple', 'Zebra'], Milestone::where('name', '!=', 'Unreviewed')->where('name', '!=', 'Backlog')->where('name', '!=', 'Future Backlog')->where('name', '!=', 'Scheduled')->orderBy('name')->pluck('name')->toArray());
    }

    #[Test]
    public function print_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->get("/milestone/print/{$milestone->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function print_eager_loads_ticket_relationships(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $ticket = Ticket::factory()->create(['milestone_id' => $milestone->id]);

        $response = $this->actingAs($user)->get("/milestone/print/{$milestone->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function print_returns_404_for_nonexistent_milestone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/milestone/print/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function get_show_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->get("/milestone/show/{$milestone->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function get_show_eager_loads_watchers_and_tickets(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        MilestoneWatcher::factory()->create(['milestone_id' => $milestone->id]);
        Ticket::factory()->create(['milestone_id' => $milestone->id]);

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function get_show_calculates_completion_percentage(): void
    {
        $user = User::factory()->create();
        $openStatus = Status::factory()->create(['name' => 'Open']);
        $closedStatus = Status::factory()->closed()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $openStatus->id,
        ]);
        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $closedStatus->id,
        ]);

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 50);
    }

    #[Test]
    public function get_show_returns_100_percent_when_all_complete(): void
    {
        $user = User::factory()->create();
        $closedStatus = Status::factory()->closed()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $closedStatus->id,
        ]);

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 100);
    }

    #[Test]
    public function get_show_returns_0_percent_when_no_tickets(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 0);
    }

    #[Test]
    public function get_show_builds_status_codes_array(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        Status::factory()->create(['name' => 'Open']);

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('statuscodes');
    }

    #[Test]
    public function get_show_renders_ticket_updated_timestamps(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create(['name' => 'Open']);
        $milestone = Milestone::factory()->create();
        $ticket = Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $status->id,
            'updated_at' => now()->setDate(2026, 3, 22)->setTime(9, 15, 0),
        ]);

        $response = $this->actingAs($user)->get("/milestone/show/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertSee(date('M jS, Y g:ia', strtotime($ticket->updated_at)));
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->get('/milestone/create');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function create_returns_create_view_with_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/milestone/create');

        $response->assertStatus(200);
        $response->assertViewHas('users');
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->get("/milestone/edit/{$milestone->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function edit_returns_edit_view_with_milestone_and_users(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->get("/milestone/edit/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('milestone');
        $response->assertViewHas('users');
    }

    #[Test]
    public function edit_allows_authenticated_users_when_milestone_has_no_owner_or_scrummaster(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create([
            'owner_user_id' => null,
            'scrummaster_user_id' => null,
        ]);

        $response = $this->actingAs($user)->get("/milestone/edit/{$milestone->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function update_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->put("/milestone/update/{$milestone->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function update_validates_input(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->put("/milestone/update/{$milestone->id}", [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function update_updates_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put("/milestone/update/{$milestone->id}", [
            'name' => 'Updated Name',
        ]);

        $milestone->refresh();
        $this->assertEquals('Updated Name', $milestone->name);
    }

    #[Test]
    public function update_redirects_to_milestone_show(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->put("/milestone/update/{$milestone->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect("/milestone/show/{$milestone->id}");
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->post('/milestone/store/new', [
            'id' => 'new',
            'name' => 'New Milestone',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function store_creates_new_milestone_when_id_is_new(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/milestone/store/new', [
            'id' => 'new',
            'name' => 'New Milestone',
        ]);

        $response->assertRedirect('/milestone');
        $this->assertDatabaseHas('milestones', ['name' => 'New Milestone']);
    }

    #[Test]
    public function store_sets_active_to_1_on_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/milestone/store/new', [
            'id' => 'new',
            'name' => 'New Milestone',
        ]);

        $this->assertDatabaseHas('milestones', ['name' => 'New Milestone', 'active' => 1]);
    }

    #[Test]
    public function store_updates_existing_milestone_when_id_is_numeric(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->post("/milestone/store/{$milestone->id}", [
            'id' => (string) $milestone->id,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect('/milestone');
        $milestone->refresh();
        $this->assertEquals('Updated Name', $milestone->name);
    }

    #[Test]
    public function store_redirects_to_milestone_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/milestone/store/new', [
            'id' => 'new',
            'name' => 'New Milestone',
        ]);

        $response->assertRedirect('/milestone');
    }

    #[Test]
    public function toggle_watcher_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->post("/milestone/watch/{$milestone->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function toggle_watcher_creates_watcher_when_not_watching(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->actingAs($user)->post("/milestone/watch/{$milestone->id}");

        $this->assertDatabaseHas('milestone_user_watchers', [
            'milestone_id' => $milestone->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function toggle_watcher_removes_watcher_when_watching(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post("/milestone/watch/{$milestone->id}");

        $this->assertDatabaseMissing('milestone_user_watchers', [
            'milestone_id' => $milestone->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function toggle_watcher_redirects_back(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->post("/milestone/watch/{$milestone->id}");

        $response->assertRedirect();
    }

    #[Test]
    public function report_requires_authentication(): void
    {
        $milestone = Milestone::factory()->create();

        $response = $this->get("/milestone/report/{$milestone->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function report_calculates_ticket_statistics(): void
    {
        $user = User::factory()->create();
        $openStatus = Status::factory()->create(['name' => 'Open']);
        $closedStatus = Status::factory()->closed()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create(['milestone_id' => $milestone->id, 'status_id' => $openStatus->id]);
        Ticket::factory()->create(['milestone_id' => $milestone->id, 'status_id' => $closedStatus->id]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('totalTickets', 2);
        $response->assertViewHas('completedTickets', 1);
        $response->assertViewHas('openTickets', 1);
    }

    #[Test]
    public function report_calculates_storypoint_statistics(): void
    {
        $user = User::factory()->create();
        $closedStatus = Status::factory()->closed()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $closedStatus->id,
            'storypoints' => 5,
        ]);
        Ticket::factory()->create([
            'milestone_id' => $milestone->id,
            'status_id' => $closedStatus->id,
            'storypoints' => 3,
        ]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('totalStoryPoints', 8);
        $response->assertViewHas('completedStoryPoints', 8);
        $response->assertViewHas('remainingStoryPoints', 0);
    }

    #[Test]
    public function report_builds_status_breakdown(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create(['milestone_id' => $milestone->id, 'status_id' => $status->id]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('statusBreakdown');
    }

    #[Test]
    public function report_builds_type_breakdown(): void
    {
        $user = User::factory()->create();
        $type = Type::factory()->create();
        $milestone = Milestone::factory()->create();

        Ticket::factory()->create(['milestone_id' => $milestone->id, 'type_id' => $type->id]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('typeBreakdown');
    }

    #[Test]
    public function report_calculates_team_hours(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $ticket = Ticket::factory()->create(['milestone_id' => $milestone->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 5,
        ]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('teamHours');
    }

    #[Test]
    public function report_builds_burndown_data_when_dates_set(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create([
            'start_at' => '2024-01-01',
            'due_at' => '2024-01-10',
        ]);
        Ticket::factory()->create(['milestone_id' => $milestone->id]);

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('burndownData');
        $response->assertViewHas('duration', 9);
    }

    #[Test]
    public function report_returns_empty_burndown_when_no_dates(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewHas('burndownData', []);
    }

    #[Test]
    public function report_returns_report_view(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->get("/milestone/report/{$milestone->id}");

        $response->assertStatus(200);
        $response->assertViewIs('milestone.report');
    }

    #[Test]
    public function store_rejects_empty_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/milestone/store/new', [
            'id' => 'new',
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }
}
