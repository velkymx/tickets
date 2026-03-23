<?php

namespace Tests\Feature\Integration;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImportLifecycleTest extends TestCase
{
    use SeedsDatabase;

    protected User $user;

    protected Milestone $milestone;

    protected Type $type;

    protected Importance $importance;

    protected Project $project;

    protected Status $status;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create(['name' => 'Test User']);
        $this->type = Type::factory()->create(['name' => 'Bug']);
        $this->importance = Importance::factory()->create(['name' => 'High']);
        $this->status = Status::factory()->create(['name' => 'Open']);
        $this->project = Project::factory()->create(['name' => 'Test Project']);
        $this->milestone = Milestone::factory()->create(['name' => 'Sprint 1']);
    }

    #[Test]
    public function it_imports_csv_creates_tickets(): void
    {
        $csv = 'Bug,Test Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertSessionHas('info_message');
        $this->assertDatabaseHas('tickets', ['subject' => 'Test Ticket']);
    }

    #[Test]
    public function it_resolves_type_id_importance_id_project_id_from_csv(): void
    {
        $csv = 'Bug,Test Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'type_id' => $this->type->id,
            'importance_id' => $this->importance->id,
            'project_id' => $this->project->id,
            'user_id2' => $this->user->id,
            'milestone_id' => $this->milestone->id,
        ]);
    }

    #[Test]
    public function it_rolls_back_entire_import_on_row_failure(): void
    {
        $firstRow = 'Bug,First Ticket,Description,High,Open,Test Project,Test User';
        $secondRow = 'NonexistentType,Second Ticket,Description,High,Open,Test Project,Test User';
        $csv = $firstRow."\n".$secondRow;
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => $this->milestone->id,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $this->assertDatabaseCount('tickets', 0);
    }

    #[Test]
    public function it_validates_milestone_exists(): void
    {
        $csv = 'Bug,Test Ticket,Description,High,Open,Test Project,Test User';
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $response = $this->actingAs($this->user)->post('/tickets/import', [
            'milestone_id' => 99999,
            'csv' => $file,
            'hasHeader' => false,
        ]);

        $response->assertSessionHasErrors();
    }
}
