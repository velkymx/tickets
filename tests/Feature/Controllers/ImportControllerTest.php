<?php

namespace Tests\Feature\Controllers;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportControllerTest extends TestCase
{
    use SeedsDatabase;

    protected User $user;

    protected Milestone $milestone;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->milestone = Milestone::factory()->create(['name' => 'Sprint 1']);
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
        $response = $this->actingAs($this->user)->get('/tickets/import');

        $response->assertStatus(200);
        $response->assertViewIs('import.index');
    }

    #[Test]
    public function index_passes_open_milestones_to_view(): void
    {
        Milestone::factory()->create(['name' => 'Closed Milestone', 'end_at' => now()]);

        $response = $this->actingAs($this->user)->get('/tickets/import');

        $response->assertStatus(200);
        $milestones = $response->viewData('milestones');
        $this->assertContains('Sprint 1', $milestones->toArray());
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.csv', 'data');

        $response = $this->post('/tickets/import', [
            'milestone_id' => 1,
            'csv' => $file,
        ]);

        $response->assertRedirect('/login');
    }

    #[Test]
    public function create_imports_csv_and_creates_tickets(): void
    {
        Type::factory()->create(['name' => 'Bug']);
        Importance::factory()->create(['name' => 'High']);
        Status::factory()->create(['name' => 'Open']);
        Project::factory()->create(['name' => 'Test Project']);

        $csv = 'Bug,Test Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertRedirect("/milestone/show/{$this->milestone->id}");
        $response->assertSessionHas('info_message', 'Tickets Successfully Imported');
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'milestone_id' => $this->milestone->id,
        ]);
    }

    #[Test]
    public function create_rolls_back_on_import_failure(): void
    {
        $csv = 'NonexistentType,Bad Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function create_shows_error_on_failure(): void
    {
        $csv = 'NonexistentType,Bad Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertRedirect('/tickets/import');
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function create_requires_csv_file(): void
    {
        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
        ]);

        $response->assertSessionHasErrors('csv');
    }

    #[Test]
    public function create_rejects_invalid_milestone(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.csv', 'data');

        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => 99999,
            'csv' => $file,
        ]);

        $response->assertSessionHasErrors('milestone_id');
    }
}
