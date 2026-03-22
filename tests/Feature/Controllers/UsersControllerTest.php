<?php

namespace Tests\Feature\Controllers;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function show_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->get("/users/{$user->id}");

        $response->assertRedirect('/login');
    }

    #[Test]
    public function show_returns_user_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertViewIs('users.show');
    }

    #[Test]
    public function show_returns_403_for_different_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/users/99999');

        $response->assertStatus(403);
    }

    #[Test]
    public function show_blocks_viewing_other_users_profile(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($me)->get("/users/{$other->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function show_loads_user_tickets_grouped_by_status(): void
    {
        $user = User::factory()->create();
        $status = Status::factory()->create(['name' => 'Open']);
        Ticket::factory()->create([
            'user_id2' => $user->id,
            'status_id' => $status->id,
        ]);

        $response = $this->actingAs($user)->get("/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertViewHas('user');
        $response->assertViewHas('alltickets');
        $response->assertViewHas('currenttime');
    }

    #[Test]
    public function show_passes_current_time_to_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get("/users/{$user->id}");

        $response->assertStatus(200);
        $response->assertViewHas('currenttime');
    }

    #[Test]
    public function edit_requires_authentication(): void
    {
        $response = $this->get('/user/edit');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function edit_returns_edit_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/edit');

        $response->assertStatus(200);
        $response->assertViewIs('users.edit');
    }

    #[Test]
    public function edit_passes_user_timezones_and_themes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/edit');

        $response->assertStatus(200);
        $response->assertViewHas('timezones');
        $response->assertViewHas('themes');
    }

    #[Test]
    public function update_requires_authentication(): void
    {
        $response = $this->post('/user/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function update_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => '',
            'email' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    #[Test]
    public function update_validates_email_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => 'Test User',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function update_updates_user_profile(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
            'theme' => 'simplex',
        ]);

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '1234567890',
            'timezone' => 'America/New_York',
            'theme' => 'simplex',
            'title' => 'Developer',
            'bio' => 'Test bio',
        ]);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals('1234567890', $user->phone);
        $this->assertEquals('America/New_York', $user->timezone);
    }

    #[Test]
    public function update_redirects_to_user_show(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
            'theme' => 'simplex',
        ]);

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'timezone' => 'America/New_York',
            'theme' => 'simplex',
        ]);

        $response->assertRedirect("/users/{$user->id}");
    }

    #[Test]
    public function update_rejects_duplicate_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'me@example.com']);

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'timezone' => 'UTC',
            'theme' => 'simplex',
        ]);

        $response->assertSessionHasErrors('email');
    }

    #[Test]
    public function update_allows_keeping_own_email(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'timezone' => 'UTC',
            'theme' => 'simplex',
        ]);

        $response = $this->actingAs($user)->post('/user/update', [
            'name' => 'Updated Name',
            'email' => 'me@example.com',
            'timezone' => 'UTC',
            'theme' => 'simplex',
        ]);

        $response->assertRedirect("/users/{$user->id}");
    }
}
