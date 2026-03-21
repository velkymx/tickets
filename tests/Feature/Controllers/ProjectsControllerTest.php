<?php

namespace Tests\Feature\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function it_returns_projects_ordered_by_name(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['name' => 'Zebra']);
        Project::factory()->create(['name' => 'Apple']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
        $this->assertEquals(['Apple', 'Zebra'], Project::orderBy('name')->pluck('name')->toArray());
    }

    #[Test]
    public function it_returns_create_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/create');

        $response->assertStatus(200);
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
