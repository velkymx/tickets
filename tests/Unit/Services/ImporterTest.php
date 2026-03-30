<?php

namespace Tests\Unit\Services;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use App\Services\Importer;
use Exception;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImporterTest extends TestCase
{
    use SeedsDatabase;

    protected Importer $importer;

    protected string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importer = new Importer;
        $this->tempFile = tempnam(sys_get_temp_dir(), 'csv');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    protected function writeCsv(array $rows): void
    {
        $handle = fopen($this->tempFile, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    #[Test]
    public function it_imports_csv_rows_as_tickets(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test Ticket', 'Description', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $this->assertEquals(1, Ticket::count());
        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'description' => 'Description',
            'milestone_id' => $milestone->id,
        ]);
    }

    #[Test]
    public function it_skips_header_row_when_flagged(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Type', 'Subject', 'Description', 'Importance', 'Status', 'Project', 'Assignee'],
            ['Bug', 'Test Ticket', 'Description', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, true);

        $this->assertEquals(1, Ticket::count());
        $this->assertDatabaseHas('tickets', ['subject' => 'Test Ticket']);
    }

    #[Test]
    public function it_does_not_skip_first_row_when_no_header(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'First Ticket', 'Description', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Second Ticket', 'Description', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $this->assertEquals(2, Ticket::count());
    }

    #[Test]
    public function it_assigns_correct_milestone_to_all_tickets(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Ticket 1', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Ticket 2', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $tickets = Ticket::all();
        $this->assertTrue($tickets->every(fn ($t) => $t->milestone_id === $milestone->id));
    }

    #[Test]
    public function it_resolves_type_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Feature']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Feature', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($type->id, $ticket->type_id);
    }

    #[Test]
    public function it_resolves_importance_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'Critical']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'Critical', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($importance->id, $ticket->importance_id);
    }

    #[Test]
    public function it_resolves_status_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Closed']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Closed', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($status->id, $ticket->status_id);
    }

    #[Test]
    public function it_resolves_project_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'My Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open', 'My Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($project->id, $ticket->project_id);
    }

    #[Test]
    public function it_resolves_user_by_name(): void
    {
        $user = User::factory()->create(['name' => 'Creator']);
        $assignee = User::factory()->create(['name' => 'Developer']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'Developer'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($assignee->id, $ticket->user_id2);
    }

    #[Test]
    public function it_sets_creator_from_user_id_parameter(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        // No Auth::login() — simulates CLI/queue context
        $this->importer->call($milestone->id, $this->tempFile, false, $creator->id);

        $ticket = Ticket::first();
        $this->assertEquals($creator->id, $ticket->user_id);
    }

    #[Test]
    public function it_falls_back_to_auth_user_when_no_user_id_given(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($creator);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->importer->call($milestone->id, $this->tempFile, false);

        $ticket = Ticket::first();
        $this->assertEquals($creator->id, $ticket->user_id);
    }

    #[Test]
    public function it_throws_on_missing_type(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['NonexistentType', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Type NonexistentType does not exist');

        $this->importer->call($milestone->id, $this->tempFile, false);
    }

    #[Test]
    public function it_throws_on_missing_user(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open', 'Test Project', 'NonexistentUser'],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User NonexistentUser does not exist');

        $this->importer->call($milestone->id, $this->tempFile, false);
    }

    #[Test]
    public function it_throws_on_row_with_fewer_than_7_columns(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Test', 'Desc', 'High', 'Open'],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CSV row must have at least 7 columns');

        $this->importer->call($milestone->id, $this->tempFile, false);
    }

    #[Test]
    public function it_rolls_back_all_rows_on_failure(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Good Ticket 1', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Good Ticket 2', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Bad Ticket', 'Desc', 'High', 'Open', 'Test Project', 'BadAssignee'],
        ]);

        try {
            $this->importer->call($milestone->id, $this->tempFile, false);
        } catch (Exception $e) {
        }

        $this->assertEquals(0, Ticket::count());
    }

    #[Test]
    public function it_caches_model_lookups(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $milestone = Milestone::factory()->create();
        $type = Type::factory()->create(['name' => 'Bug']);
        $importance = Importance::factory()->create(['name' => 'High']);
        $status = Status::factory()->create(['name' => 'Open']);
        $project = Project::factory()->create(['name' => 'Test Project', 'active' => true]);

        Auth::login($user);

        $this->writeCsv([
            ['Bug', 'Ticket 1', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Ticket 2', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
            ['Bug', 'Ticket 3', 'Desc', 'High', 'Open', 'Test Project', 'Assignee'],
        ]);

        $importer = new class extends Importer
        {
            public array $models = [];

            public function getModels(): array
            {
                return $this->models;
            }
        };

        $reflection = new \ReflectionClass(Importer::class);
        $property = $reflection->getProperty('models');
        $property->setAccessible(true);

        $importer->call($milestone->id, $this->tempFile, false);

        $models = $property->getValue($importer);
        $this->assertArrayHasKey('App\Models\Type|Bug', $models);
    }
}
