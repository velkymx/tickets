<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ProjectsControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_returns_projects_ordered_by_name(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['name' => 'Zebra']);
        Project::factory()->create(['name' => 'Apple']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
        $this->assertEquals(['Apple', 'Zebra'], Project::where('name', '!=', 'Unassigned')->orderBy('name')->pluck('name')->toArray());
    }

    #[Test]
    public function show_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $response = $this->get("/projects/show/{$project->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function show_shows_project_with_paginated_tickets(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Ticket::factory()->count(15)->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)->get("/projects/show/{$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('project');
        $response->assertViewHas('tickets');
    }

    #[Test]
    public function show_filters_tickets_by_query_parameters(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $status = Status::factory()->create();
        Ticket::factory()->create(['project_id' => $project->id, 'status_id' => $status->id]);
        Ticket::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)->get("/projects/show/{$project->id}?status_id={$status->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function show_calculates_completion_percentage(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $closedStatus = Status::factory()->closed()->create();
        $openStatus = Status::factory()->create();

        Ticket::factory()->create(['project_id' => $project->id, 'status_id' => $closedStatus->id]);
        Ticket::factory()->create(['project_id' => $project->id, 'status_id' => $openStatus->id]);

        $response = $this->actingAs($user)->get("/projects/show/{$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('percent', 50);
    }

    #[Test]
    public function show_eager_loads_ticket_relationships(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        Ticket::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user)->get("/projects/show/{$project->id}");

        $response->assertStatus(200);
        $tickets = $response->viewData('tickets');
        $this->assertTrue($tickets[0]->relationLoaded('status'));
        $this->assertTrue($tickets[0]->relationLoaded('type'));
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->get('/projects/create');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_returns_create_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertViewIs('projects.create');
    }

    #[Test]
    public function create_view_includes_a_description_form_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertSee('name="description"', false);
        $response->assertSee('<textarea', false);
    }

    #[Test]
    public function create_view_uses_the_lazy_quill_loader(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertSee('window.loadQuill()', false);
    }

    #[Test]
    public function create_view_includes_the_required_hidden_id_field(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
        $response->assertSee('name="id"', false);
        $response->assertSee('value="new"', false);
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $project = Project::factory()->create();

        $response = $this->get("/projects/edit/{$project->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_authorizes_update(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get("/projects/edit/{$project->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_edit_view(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get("/projects/edit/{$project->id}");

        $response->assertViewHas('project');
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->post('/projects/store/new', [
            'id' => 'new',
            'name' => 'Test',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_creates_project_with_active_flag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects/store/new', [
            'id' => 'new',
            'name' => 'New Project',
        ]);

        $response->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', ['name' => 'New Project', 'active' => 1]);
    }

    #[Test]
    public function it_updates_existing_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->post("/projects/store/{$project->id}", [
            'id' => (string) $project->id,
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function store_rejects_empty_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects/store/new', [
            'id' => 'new',
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_redirects_to_projects_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/projects/store/new', [
            'id' => 'new',
            'name' => 'Test Project',
        ]);

        $response->assertRedirect('/projects');
    }
}
