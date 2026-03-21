<?php

namespace Tests\Feature\Controllers;

use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReleaseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/releases');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_returns_releases_view(): void
    {
        $user = User::factory()->create();
        Release::factory()->create();

        $response = $this->actingAs($user)->get('/releases');

        $response->assertStatus(200);
        $response->assertViewIs('release.index');
    }

    #[Test]
    public function index_passes_releases_to_view(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create();

        $response = $this->actingAs($user)->get('/releases');

        $response->assertStatus(200);
        $response->assertViewHas('releases');
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->get('/releases/create');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function create_returns_create_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/releases/create');

        $response->assertStatus(200);
        $response->assertViewIs('release.create');
    }

    #[Test]
    public function show_requires_authentication(): void
    {
        $release = Release::factory()->create();

        $response = $this->get("/release/{$release->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function show_returns_release_view(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create();

        $response = $this->actingAs($user)->get("/release/{$release->id}");

        $response->assertStatus(200);
        $response->assertViewIs('release.show');
    }

    #[Test]
    public function show_returns_404_for_nonexistent_release(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/release/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function show_loads_release_with_owner(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/release/{$release->id}");

        $response->assertStatus(200);
    }

    #[Test]
    public function show_loads_release_tickets_grouped_by_project_and_type(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create();
        $ticket = Ticket::factory()->create();
        $type = Type::factory()->create();
        ReleaseTicket::factory()->create([
            'release_id' => $release->id,
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->actingAs($user)->get("/release/{$release->id}");

        $response->assertStatus(200);
        $response->assertViewHas('projects');
        $response->assertViewHas('types');
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $release = Release::factory()->create();

        $response = $this->get("/release/edit/{$release->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function edit_returns_release_view(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/release/edit/{$release->id}");

        $response->assertStatus(200);
        $response->assertViewIs('release.edit');
    }

    #[Test]
    public function put_requires_authentication(): void
    {
        $release = Release::factory()->create();

        $response = $this->put("/release/edit/{$release->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function put_updates_release(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/release/edit/{$release->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
        ]);

        $release->refresh();
        $this->assertEquals('Updated Title', $release->title);
        $this->assertEquals('Updated Body', $release->body);
    }

    #[Test]
    public function put_redirects_to_release_show(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/release/edit/{$release->id}", [
            'title' => 'Updated Title',
            'body' => 'Updated Body',
        ]);

        $response->assertRedirect("/release/{$release->id}");
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->post('/release/store', [
            'title' => 'New Release',
            'body' => 'Release Body',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function store_creates_new_release(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/release/store', [
            'title' => 'New Release',
            'body' => 'Release Body',
            'started_at' => '2024-01-01',
            'completed_at' => '2024-01-31',
        ]);

        $this->assertDatabaseHas('releases', [
            'title' => 'New Release',
            'body' => 'Release Body',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function store_redirects_to_release_show(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/release/store', [
            'title' => 'New Release',
            'body' => 'Release Body',
        ]);

        $release = Release::first();
        $response->assertRedirect("/release/{$release->id}");
    }

    #[Test]
    public function store_sets_user_id_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/release/store', [
            'title' => 'New Release',
            'body' => 'Release Body',
        ]);

        $release = Release::first();
        $this->assertEquals($user->id, $release->user_id);
    }
}
