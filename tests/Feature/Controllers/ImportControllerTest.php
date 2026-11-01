<?php

namespace Tests\Feature\Controllers;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImportControllerTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
    }

    #[Test]
    public function index_returns_import_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tickets/import');

        $response->assertStatus(200);
        $response->assertViewIs('import.index');
    }

    #[Test]
    public function index_passes_open_milestones_to_view(): void
    {
        $user = User::factory()->create();
        Milestone::factory()->create(['name' => 'Sprint 1']);
        Milestone::factory()->create(['name' => 'Closed Milestone', 'end_at' => now()]);

        $response = $this->actingAs($user)->get('/tickets/import');

        $response->assertStatus(200);
        $milestones = $response->viewData('milestones');
        $this->assertContains('Sprint 1', $milestones->toArray());
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $file = UploadedFile::fake()->createWithContent('import.csv', 'data');

        $before = Ticket::count();

        $this->post('/tickets/import', [
            'milestone_id' => 1,
            'csv' => $file,
        ])->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame($before, Ticket::count());
    }

    #[Test]
    public function create_imports_csv_and_creates_tickets(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $milestone = Milestone::factory()->create(['name' => 'Sprint 1']);
        Type::factory()->create(['name' => 'Bug']);
        Importance::factory()->create(['name' => 'High']);
        Status::factory()->create(['name' => 'Open']);
        Project::factory()->create(['name' => 'Test Project']);

        $csv = 'Bug,Test Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($user)->post('/tickets/import', [
            'milestone_id' => $milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertRedirect("/milestone/show/{$milestone->id}");
        $response->assertSessionHas('info_message', 'Tickets Successfully Imported');
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'milestone_id' => $milestone->id,
        ]);
    }

    #[Test]
    public function create_rolls_back_on_import_failure(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $csv = 'NonexistentType,Bad Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($user)->post('/tickets/import', [
            'milestone_id' => $milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function create_shows_error_on_failure(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $csv = 'NonexistentType,Bad Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($user)->post('/tickets/import', [
            'milestone_id' => $milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertRedirect('/tickets/import');
        $response->assertSessionHasErrors();
    }

    #[Test]
    public function create_requires_csv_file(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $response = $this->actingAs($user)->post('/tickets/import', [
            'milestone_id' => $milestone->id,
        ]);

        $response->assertSessionHasErrors('csv');
    }

    #[Test]
    public function create_rejects_invalid_milestone(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('import.csv', 'data');

        $response = $this->actingAs($user)->post('/tickets/import', [
            'milestone_id' => 99999,
            'csv' => $file,
        ]);

        $response->assertSessionHasErrors('milestone_id');
    }
}
