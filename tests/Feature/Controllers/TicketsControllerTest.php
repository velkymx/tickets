<?php

namespace Tests\Feature\Controllers;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function home_requires_authentication(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function home_shows_tickets_assigned_to_current_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $myTicket = Ticket::factory()->create(['user_id2' => $user->id]);
        $otherTicket = Ticket::factory()->create(['user_id2' => $otherUser->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee($myTicket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function home_excludes_closed_tickets(): void
    {
        $user = User::factory()->create();

        $openStatus = Status::factory()->create(['name' => 'Open Status']);
        $closedStatus = Status::factory()->closed()->create();

        $openTicket = Ticket::factory()->create([
            'user_id2' => $user->id,
            'status_id' => $openStatus->id,
        ]);

        $closedTicket = Ticket::factory()->create([
            'user_id2' => $user->id,
            'status_id' => $closedStatus->id,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee($openTicket->subject);
        $response->assertDontSee($closedTicket->subject);
    }

    #[Test]
    public function home_groups_tickets_by_status_name(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create(['name' => 'In Progress']);

        Ticket::factory()->create([
            'user_id2' => $user->id,
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('In Progress');
    }

    #[Test]
    public function home_returns_home_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/tickets');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_returns_paginated_tickets(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertViewHas('tickets');
    }

    #[Test]
    public function index_filters_by_milestone_id(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $otherMilestone = Milestone::factory()->create();

        $ticket = Ticket::factory()->create(['milestone_id' => $milestone->id]);
        $otherTicket = Ticket::factory()->create(['milestone_id' => $otherMilestone->id]);

        $response = $this->actingAs($user)->get("/tickets?milestone_id={$milestone->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function index_filters_by_project_id(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        $ticket = Ticket::factory()->create(['project_id' => $project->id]);
        $otherTicket = Ticket::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($user)->get("/tickets?project_id={$project->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function index_filters_by_status_id(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create();
        $otherStatus = Status::factory()->create();

        $ticket = Ticket::factory()->create(['status_id' => $status->id]);
        $otherTicket = Ticket::factory()->create(['status_id' => $otherStatus->id]);

        $response = $this->actingAs($user)->get("/tickets?status_id={$status->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function index_filters_by_type_id(): void
    {
        $user = User::factory()->create();
        $type = Type::factory()->create();
        $otherType = Type::factory()->create();

        $ticket = Ticket::factory()->create(['type_id' => $type->id]);
        $otherTicket = Ticket::factory()->create(['type_id' => $otherType->id]);

        $response = $this->actingAs($user)->get("/tickets?type_id={$type->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function index_filters_by_importance_id(): void
    {
        $user = User::factory()->create();
        $importance = Importance::factory()->create();
        $otherImportance = Importance::factory()->create();

        $ticket = Ticket::factory()->create(['importance_id' => $importance->id]);
        $otherTicket = Ticket::factory()->create(['importance_id' => $otherImportance->id]);

        $response = $this->actingAs($user)->get("/tickets?importance_id={$importance->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
        $response->assertDontSee($otherTicket->subject);
    }

    #[Test]
    public function index_filters_by_user_id(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ticket = Ticket::factory()->create(['user_id' => $otherUser->id]);
        $otherTicket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets?user_id={$otherUser->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
    }

    #[Test]
    public function index_searches_by_subject_with_q_parameter(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()->create(['subject' => 'Unique Search Term Here']);
        $otherTicket = Ticket::factory()->create(['subject' => 'Something Else']);

        $response = $this->actingAs($user)->get('/tickets?q=Unique Search Term Here');

        $response->assertStatus(200);
        $response->assertSee('Unique Search Term Here');
        $response->assertDontSee('Something Else');
    }

    #[Test]
    public function index_filters_active_statuses_when_status_is_none(): void
    {
        $user = User::factory()->create();

        $openStatus = Status::factory()->create(['name' => 'Open Status']);
        $closedStatus = Status::factory()->closed()->create();

        $openTicket = Ticket::factory()->create([
            'status_id' => $openStatus->id,
        ]);

        $closedTicket = Ticket::factory()->create([
            'status_id' => $closedStatus->id,
        ]);

        $response = $this->actingAs($user)->get('/tickets?status_id=none');

        $response->assertStatus(200);
        $response->assertSee($openTicket->subject);
        $response->assertDontSee($closedTicket->subject);
    }

    #[Test]
    public function index_respects_perpage_parameter(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(25)->create();

        $response = $this->actingAs($user)->get('/tickets?perpage=5');

        $response->assertStatus(200);
        $response->assertViewHas('tickets', function ($tickets) {
            return $tickets->perPage() === 5;
        });
    }

    #[Test]
    public function index_defaults_to_10_per_page(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(15)->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertViewHas('tickets', function ($tickets) {
            return $tickets->perPage() === 10;
        });
    }

    #[Test]
    public function index_includes_lookups_in_view_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tickets');

        $response->assertStatus(200);
        $response->assertViewHas('lookups');
        $response->assertViewHas('viewfilters');
    }

    #[Test]
    public function show_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->get("/tickets/{$ticket->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function show_shows_ticket_details(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->subject);
    }

    #[Test]
    public function show_creates_ticket_view_record(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $this->assertDatabaseHas('ticket_views', [
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
    }

    #[Test]
    public function show_does_not_duplicate_ticket_view(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->get("/tickets/{$ticket->id}");
        $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $count = TicketView::where('user_id', $user->id)
            ->where('ticket_id', $ticket->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function show_returns_404_for_nonexistent_ticket(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function show_renders_the_ticket_pulse_panel(): void
    {
        $user = User::factory()->create(['name' => 'John']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $thread = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => "Race condition discussion\nMore detail",
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $thread->id,
            'body' => 'Reply',
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'decision',
            'body' => 'Use Redis locks',
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'action',
            'body' => 'QA verification @john',
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('Ticket Pulse');
        $response->assertSee('position: sticky', false);
        $response->assertSee('Next Action');
        $response->assertSee('Latest Decision');
        $response->assertSee('Open Threads');
        $response->assertSee('Last Update');
        $response->assertSee('Pulse Summary');
    }

    #[Test]
    public function show_renders_promote_actions_for_message_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'This note should be promotable to a signal.',
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('Promote to Decision');
        $response->assertSee('Promote to Blocker');
        $response->assertSee('Promote to Action');
        $response->assertSee('This will surface in Ticket Pulse. All team members will see it.');
    }

    #[Test]
    public function show_passes_ticket_pulse_to_the_view(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertViewHas('pulse', function ($pulse) use ($ticket) {
            return $pulse instanceof \App\ValueObjects\TicketPulse
                && $pulse->id === $ticket->id;
        });
    }

    #[Test]
    public function show_eager_loads_unified_timeline_data(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $parent = Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'pinned' => true,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertViewHas('allUsers');
        $response->assertViewHas('pinnedNotes');
        $response->assertViewHas('lastViewedAt');
    }

    #[Test]
    public function show_renders_unified_timeline_with_all_note_types(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'message',
            'body' => 'A regular comment',
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'changelog',
            'body' => 'Status changed',
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'decision',
            'body' => 'We decided to use Redis',
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('activity-timeline');
        $response->assertSee('A regular comment');
        $response->assertSee('Status changed');
        $response->assertSee('We decided to use Redis');
        $response->assertSee('data-entry-type="message"', false);
        $response->assertSee('data-entry-type="changelog"', false);
        $response->assertSee('data-entry-type="decision"', false);
    }

    #[Test]
    public function show_renders_pinned_notes_at_top_of_timeline(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'pinned' => true,
            'body' => 'This is pinned',
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('pinned-notes-section');
        $response->assertSee('This is pinned');
    }

    #[Test]
    public function show_renders_unread_divider_for_new_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        // Create a view record in the past
        $ticketView = TicketView::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
        ]);
        TicketView::where('id', $ticketView->id)->update(['updated_at' => now()->subHour()]);

        // Create a note after the last view
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'New note after view',
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('unread-divider');
    }

    #[Test]
    public function show_renders_filter_buttons_for_timeline(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee('timeline-filter');
        $response->assertSee('All');
        $response->assertSee('Comments');
        $response->assertSee('Decisions');
        $response->assertSee('Blockers');
        $response->assertSee('Activity');
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->get('/ticket/create');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function create_returns_create_view_with_lookups(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/ticket/create');

        $response->assertStatus(200);
        $response->assertViewIs('tickets.create');
        $response->assertViewHas('lookups');
    }

    #[Test]
    public function clone_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->get("/tickets/clone/{$ticket->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function clone_returns_clone_view_with_ticket_data(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets/clone/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertViewIs('tickets.clone');
        $response->assertViewHas('ticket');
    }

    #[Test]
    public function clone_returns_404_for_nonexistent_ticket(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/clone/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->get("/tickets/edit/{$ticket->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function edit_returns_edit_view_with_ticket_data(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/tickets/edit/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertViewIs('tickets.edit');
        $response->assertViewHas('ticket');
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->post('/tickets', [
            'subject' => 'Test Ticket',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function store_creates_ticket_with_validated_data(): void
    {
        $user = User::factory()->create();
        $type = Type::factory()->create();
        $status = Status::factory()->create();
        $importance = Importance::factory()->create();
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->post('/tickets', [
            'subject' => 'New Test Ticket',
            'description' => 'Test description',
            'type_id' => $type->id,
            'status_id' => $status->id,
            'importance_id' => $importance->id,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'New Test Ticket',
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
    }

    #[Test]
    public function store_sets_user_id_to_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tickets', [
            'subject' => 'Test Ticket',
            'description' => 'Test description',
            'type_id' => Type::factory()->create()->id,
            'status_id' => Status::factory()->create()->id,
            'importance_id' => Importance::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'milestone_id' => Milestone::factory()->create()->id,
        ]);

        $ticket = Ticket::where('subject', 'Test Ticket')->first();
        $this->assertEquals($user->id, $ticket->user_id);
    }

    #[Test]
    public function store_sets_user_id2_to_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tickets', [
            'subject' => 'Test Ticket',
            'description' => 'Test description',
            'type_id' => Type::factory()->create()->id,
            'status_id' => Status::factory()->create()->id,
            'importance_id' => Importance::factory()->create()->id,
            'project_id' => Project::factory()->create()->id,
            'milestone_id' => Milestone::factory()->create()->id,
        ]);

        $ticket = Ticket::where('subject', 'Test Ticket')->first();
        $this->assertEquals($user->id, $ticket->user_id2);
    }

    #[Test]
    public function store_redirects_to_new_ticket(): void
    {
        $user = User::factory()->create();
        $type = Type::factory()->create();
        $status = Status::factory()->create();
        $importance = Importance::factory()->create();
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->post('/tickets', [
            'subject' => 'New Ticket',
            'description' => 'Test description',
            'type_id' => $type->id,
            'status_id' => $status->id,
            'importance_id' => $importance->id,
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
        ]);

        $ticket = Ticket::where('subject', 'New Ticket')->first();
        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function store_rejects_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tickets', [
            'subject' => '',
        ]);

        $response->assertSessionHasErrors('subject');
    }

    #[Test]
    public function update_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function update_authorizes_ticket_owner(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
            'description' => 'Updated description',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function update_authorizes_ticket_assignee(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id2' => $user->id]);

        $response = $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
            'description' => 'Updated description',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function update_denies_unrelated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $otherUser->id,
            'user_id2' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
            'description' => 'Updated description',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function update_updates_ticket_with_validated_data(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
            'description' => 'Updated description',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Updated Subject',
        ]);
    }

    #[Test]
    public function update_nullifies_empty_due_at(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'due_at' => '2024-01-01',
        ]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
            'due_at' => '',
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->due_at);
    }

    #[Test]
    public function update_redirects_back_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated Subject',
            'description' => 'Updated description',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function update_formats_due_at(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
            'due_at' => '2024-06-15 10:30:00',
        ]);

        $ticket->refresh();
        $this->assertStringStartsWith('2024-06-15', $ticket->due_at);
    }

    #[Test]
    public function update_formats_closed_at(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
            'closed_at' => '2024-06-15 10:30:00',
        ]);

        $ticket->refresh();
        $this->assertStringStartsWith('2024-06-15', $ticket->closed_at);
    }

    #[Test]
    public function update_nullifies_empty_closed_at(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'type_id' => $ticket->type_id,
            'status_id' => $ticket->status_id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
            'closed_at' => '',
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    #[Test]
    public function update_creates_changelog_note_on_changes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => $ticket->subject,
            'description' => $ticket->description ?? '',
            'type_id' => $ticket->type_id,
            'status_id' => $newStatus->id,
            'importance_id' => $ticket->importance_id,
            'milestone_id' => $ticket->milestone_id,
            'project_id' => $ticket->project_id,
        ]);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function claim_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->post("/tickets/claim/{$ticket->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function claim_assigns_current_user_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/claim/{$ticket->id}");

        $ticket->refresh();
        $this->assertEquals($user->id, $ticket->user_id2);
    }

    #[Test]
    public function claim_creates_changelog_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/claim/{$ticket->id}");

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function claim_redirects_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)->post("/tickets/claim/{$ticket->id}");

        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function batch_requires_authentication(): void
    {
        $response = $this->post('/tickets/batch', [
            'tickets' => [1 => []],
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function batch_requires_at_least_one_ticket_selected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/batch', [
            'tickets' => [],
        ]);

        $response->assertSessionHasErrors('tickets');
    }

    #[Test]
    public function batch_updates_multiple_tickets(): void
    {
        $user = User::factory()->create();
        $ticket1 = Ticket::factory()->create(['user_id' => $user->id]);
        $ticket2 = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/batch', [
            'tickets' => [
                $ticket1->id => 'on',
                $ticket2->id => 'on',
            ],
            'status_id' => $newStatus->id,
        ]);

        $response->assertStatus(302);
        $ticket1->refresh();
        $ticket2->refresh();
        $this->assertEquals($newStatus->id, $ticket1->status_id);
        $this->assertEquals($newStatus->id, $ticket2->status_id);
    }

    #[Test]
    public function batch_filters_null_and_zero_update_fields(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'type_id' => Type::factory()->create()->id,
        ]);
        $originalTypeId = $ticket->type_id;

        $response = $this->actingAs($user)->post('/tickets/batch', [
            'tickets' => [$ticket->id => 1],
            'type_id' => 0,
        ]);

        $ticket->refresh();
        $this->assertEquals($originalTypeId, $ticket->type_id);
    }

    #[Test]
    public function batch_redirects_with_count_message(): void
    {
        $user = User::factory()->create();
        $ticket1 = Ticket::factory()->create(['user_id' => $user->id]);
        $ticket2 = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/batch', [
            'tickets' => [
                $ticket1->id => 1,
                $ticket2->id => 1,
            ],
            'status_id' => $newStatus->id,
        ]);

        $response->assertRedirect('/tickets');
        $response->assertSessionHas('info_message', '2 ticket(s) updated');
    }

    #[Test]
    public function board_requires_authentication(): void
    {
        $response = $this->get('/tickets/board');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function api_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->post("/tickets/api/{$ticket->id}", [
            'status' => Status::factory()->create()->id,
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function api_validates_status_exists(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/tickets/api/{$ticket->id}", [
            'status' => 99999,
        ]);

        $response->assertSessionHasErrors('status');
    }

    #[Test]
    public function api_updates_ticket_status(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $response = $this->actingAs($user)->post("/tickets/api/{$ticket->id}", [
            'status' => $newStatus->id,
        ]);

        $ticket->refresh();
        $this->assertEquals($newStatus->id, $ticket->status_id);
    }

    #[Test]
    public function api_creates_changelog_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $this->actingAs($user)->post("/tickets/api/{$ticket->id}", [
            'status' => $newStatus->id,
        ]);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function api_returns_json_success(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $response = $this->actingAs($user)->post("/tickets/api/{$ticket->id}", [
            'status' => $newStatus->id,
        ]);

        $response->assertJson(['status' => 'success']);
    }

    #[Test]
    public function api_returns_400_when_status_unchanged(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/tickets/api/{$ticket->id}", [
            'status' => $ticket->status_id,
        ]);

        $response->assertStatus(400);
    }

    #[Test]
    public function api_returns_404_for_nonexistent_ticket(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/api/99999', [
            'status' => $status->id,
        ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function note_requires_authentication(): void
    {
        $response = $this->post('/notes', [
            'ticket_id' => 1,
            'status_id' => 1,
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function note_requires_status_id_and_ticket_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notes', []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['ticket_id']);
    }

    #[Test]
    public function note_updates_status_when_changed(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $response = $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $newStatus->id,
            'note' => 'Test note',
        ]);

        $ticket->refresh();
        $this->assertEquals($newStatus->id, $ticket->status_id);
    }

    #[Test]
    public function note_sets_closed_at_when_status_is_closed(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $closedStatus = Status::factory()->closed()->create();

        $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $closedStatus->id,
            'note' => 'Closing ticket',
        ]);

        $ticket->refresh();
        $this->assertNotNull($ticket->closed_at);
    }

    #[Test]
    public function note_clears_closed_at_when_status_is_open(): void
    {
        $user = User::factory()->create();
        $openStatus = Status::factory()->create(['name' => 'Open']);
        $closedStatus = Status::factory()->closed()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'status_id' => $closedStatus->id,
            'closed_at' => now(),
        ]);

        $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $openStatus->id,
            'note' => 'Reopening ticket',
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->closed_at);
    }

    #[Test]
    public function note_creates_note_with_body(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $ticket->status_id,
            'note' => 'This is a test note',
        ]);

        $note = Note::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->where('notetype', 'message')
            ->first();

        $this->assertNotNull($note);
        $this->assertSame('This is a test note', $note->body_markdown);
        $this->assertStringContainsString('<p>This is a test note</p>', $note->body);
    }

    #[Test]
    public function note_creates_changelog_on_changes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $newStatus = Status::factory()->create();

        $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $newStatus->id,
            'note' => 'Status changed',
        ]);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function note_updates_actual_hours(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'hours' => 5,
        ]);

        $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $ticket->status_id,
            'note' => 'Adding hours',
            'hours' => 3,
        ]);

        $ticket->refresh();
        $this->assertEquals(8, $ticket->actual);
    }

    #[Test]
    public function note_redirects_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/notes', [
            'ticket_id' => $ticket->id,
            'status_id' => $ticket->status_id,
            'note' => 'Test note',
        ]);

        $response->assertRedirect("/tickets/{$ticket->id}");
    }

    #[Test]
    public function estimate_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function estimate_creates_new_estimate_for_first_vote(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $this->assertDatabaseHas('ticket_estimates', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'storypoints' => 5,
        ]);
    }

    #[Test]
    public function estimate_updates_existing_estimate(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketEstimate::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'storypoints' => 3,
        ]);

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 8,
        ]);

        $estimate = TicketEstimate::where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertEquals(8, $estimate->storypoints);
    }

    #[Test]
    public function estimate_skips_save_when_estimate_unchanged(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $estimate = TicketEstimate::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'storypoints' => 5,
        ]);

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $estimate->refresh();
        $this->assertEquals(5, $estimate->storypoints);
    }

    #[Test]
    public function estimate_calculates_fibonacci_rounded_average(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketEstimate::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'storypoints' => 1,
        ]);

        $user2 = User::factory()->create();

        $this->actingAs($user2)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $ticket->refresh();
        $this->assertEquals(3, $ticket->storypoints);
    }

    #[Test]
    public function estimate_updates_ticket_storypoints(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $ticket->refresh();
        $this->assertEquals(5, $ticket->storypoints);
    }

    #[Test]
    public function estimate_creates_changelog_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 5,
        ]);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function estimate_handles_single_voter(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 3,
        ]);

        $ticket->refresh();
        $this->assertEquals(3, $ticket->storypoints);
    }

    #[Test]
    public function estimate_handles_zero_storypoints(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 0,
        ]);

        $ticket->refresh();
        $this->assertEquals(0, $ticket->storypoints);
    }

    #[Test]
    public function estimate_caps_at_max_fibonacci_when_average_exceeds(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $ticket = Ticket::factory()->create();

        // Two users estimate 21 each, then user1 changes to 21
        // Average = (21+21)/2 = 21, should stay at 21 (not drop to 0)
        TicketEstimate::create(['ticket_id' => $ticket->id, 'user_id' => $user1->id, 'storypoints' => 13]);
        TicketEstimate::create(['ticket_id' => $ticket->id, 'user_id' => $user2->id, 'storypoints' => 21]);

        // Now user1 votes 21. New avg = (21+21)/2 = 21
        $this->actingAs($user1)->post("/tickets/estimate/{$ticket->id}", [
            'storypoints' => 21,
        ]);

        $ticket->refresh();
        $this->assertEquals(21, $ticket->storypoints);
    }

    #[Test]
    public function fetch_returns_tickets_within_date_range(): void
    {
        $user = User::factory()->create();

        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-06-10 00:00:00']);
        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-06-20 00:00:00']);
        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-07-01 00:00:00']);

        $response = $this->actingAs($user)->getJson('/tickets/fetch?started_at=2024-06-01&completed_at=2024-06-30');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    #[Test]
    public function fetch_excludes_other_users_tickets(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-06-10 00:00:00']);
        Ticket::factory()->create(['user_id2' => $otherUser->id, 'closed_at' => '2024-06-15 00:00:00']);

        $response = $this->actingAs($user)->getJson('/tickets/fetch?started_at=2024-06-01&completed_at=2024-06-30');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function fetch_requires_started_at_and_completed_at(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/tickets/fetch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['started_at', 'completed_at']);
    }

    #[Test]
    public function fetch_returns_ticket_resource_collection(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-06-15 00:00:00']);

        $response = $this->actingAs($user)->getJson('/tickets/fetch?started_at=2024-06-01&completed_at=2024-06-30');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'subject',
                    'status_id',
                    'project_id',
                    'created_at',
                ],
            ],
        ]);
    }

    #[Test]
    public function fetch_returns_empty_when_no_tickets_in_range(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['user_id2' => $user->id, 'closed_at' => '2024-07-01 00:00:00']);

        $response = $this->actingAs($user)->getJson('/tickets/fetch?started_at=2024-06-01&completed_at=2024-06-30');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    #[Test]
    public function toggle_watcher_requires_authentication(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->post("/tickets/watch/{$ticket->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function toggle_watcher_creates_watcher_when_not_watching(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)->post("/tickets/watch/{$ticket->id}");

        $this->assertDatabaseHas('ticket_user_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function toggle_watcher_removes_watcher_when_already_watching(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post("/tickets/watch/{$ticket->id}");

        $this->assertDatabaseMissing('ticket_user_watchers', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function toggle_watcher_redirects_back(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($user)->post("/tickets/watch/{$ticket->id}");

        $response->assertRedirect();
    }

    #[Test]
    public function upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'avatars',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function upload_accepts_valid_image(): void
    {
        $user = User::factory()->create();

        foreach (['jpg', 'png', 'gif'] as $ext) {
            $file = UploadedFile::fake()->image("test.{$ext}");

            $response = $this->actingAs($user)->post('/tickets/upload', [
                'file' => $file,
                'folder' => 'avatars',
            ]);

            $response->assertStatus(200);
            $this->assertStringContainsString('/images/avatars/', $response->getContent());
        }
    }

    #[Test]
    public function upload_rejects_non_image_files(): void
    {
        $user = User::factory()->create();

        foreach (['pdf', 'php'] as $ext) {
            $file = UploadedFile::fake()->create("test.{$ext}", 100);

            $response = $this->actingAs($user)->post('/tickets/upload', [
                'file' => $file,
                'folder' => 'uploads',
            ]);

            $response->assertSessionHasErrors('file');
        }
    }

    #[Test]
    public function upload_rejects_files_over_5mb(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('big.jpg')->size(6000);

        $response = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'avatars',
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[Test]
    public function upload_requires_folder_parameter(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => '',
        ]);

        $response->assertSessionHasErrors('folder');
    }

    #[Test]
    public function upload_sanitizes_folder_name(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'my!@#$%avatars',
        ]);

        $response->assertStatus(200);
        $path = $response->getContent();
        $this->assertStringContainsString('/images/myavatars/', $path);
    }

    #[Test]
    public function upload_generates_unique_filename(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response1 = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'uploads',
        ]);

        $response2 = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'uploads',
        ]);

        $this->assertNotEquals($response1->getContent(), $response2->getContent());
    }

    #[Test]
    public function upload_returns_image_path(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($user)->post('/tickets/upload', [
            'file' => $file,
            'folder' => 'avatars',
        ]);

        $response->assertStatus(200);
        $path = $response->getContent();
        $this->assertStringStartsWith('/images/avatars/', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }
}
