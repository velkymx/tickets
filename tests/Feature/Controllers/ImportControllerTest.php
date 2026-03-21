<?php

namespace Tests\Feature\Controllers;

use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
    }

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/tickets/import');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_returns_import_view(): void
    {
        $user = User::factory()->create();
        Milestone::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/import');

        $response->assertStatus(200);
        $response->assertViewIs('import.index');
    }

    #[Test]
    public function index_passes_milestones_to_view(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/import');

        $response->assertStatus(200);
        $response->assertViewHas('milestones');
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->post('/tickets/import', [
            'milestone_id' => 1,
        ]);

        $response->assertRedirect('/login');
    }
}
