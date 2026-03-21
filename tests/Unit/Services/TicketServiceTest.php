<?php

namespace Tests\Unit\Services;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Release;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\Type;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketService;
    }

    #[Test]
    public function it_detects_subject_change(): void
    {
        $old = ['id' => 1, 'subject' => 'Old Subject', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'New Subject', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Subject changed to New Subject', $changes);
    }

    #[Test]
    public function it_detects_status_change_with_lookup_name(): void
    {
        Cache::flush();
        $status = Status::factory()->create(['name' => 'Closed', 'id' => 10]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 10, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Status changed to Closed', $changes);
    }

    #[Test]
    public function it_detects_importance_change(): void
    {
        Cache::flush();
        $importance = Importance::factory()->create(['name' => 'High', 'id' => 10]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 10, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Importance changed to High', $changes);
    }

    #[Test]
    public function it_detects_type_change(): void
    {
        Cache::flush();
        $type = Type::factory()->create(['name' => 'Feature', 'id' => 10]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 10, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Type changed to Feature', $changes);
    }

    #[Test]
    public function it_detects_milestone_change(): void
    {
        Cache::flush();
        $milestone = Milestone::factory()->create(['name' => 'Sprint 2', 'id' => 10]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 10, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Milestone changed to Sprint 2', $changes);
    }

    #[Test]
    public function it_detects_project_change(): void
    {
        Cache::flush();
        $project = Project::factory()->create(['name' => 'New Project', 'id' => 10, 'active' => true]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 10, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Project changed to New Project', $changes);
    }

    #[Test]
    public function it_detects_estimate_change(): void
    {
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => '5', 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => '10', 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Estimate changed to 10', $changes);
    }

    #[Test]
    public function it_detects_storypoints_change(): void
    {
        Cache::flush();
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 3, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 5, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertNotEmpty(array_filter($changes, fn ($c) => str_contains($c, 'Storypoints changed to 5')));
    }

    #[Test]
    public function it_detects_assignee_change_and_creates_watcher(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $newAssignee = User::factory()->create();

        $this->service->getLookups();

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => $user->id, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => $newAssignee->id, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertNotEmpty(array_filter($changes, fn ($c) => str_contains($c, 'Assigned User changed to '.$newAssignee->name)));
        $this->assertDatabaseHas('ticket_user_watchers', [
            'ticket_id' => 1,
            'user_id' => $newAssignee->id,
        ]);
    }

    #[Test]
    public function it_does_not_duplicate_watcher_on_reassign(): void
    {
        Cache::flush();
        $user = User::factory()->create();
        $newAssignee = User::factory()->create();

        TicketUserWatcher::factory()->create([
            'ticket_id' => 1,
            'user_id' => $newAssignee->id,
        ]);

        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => $user->id, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => $newAssignee->id, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $this->service->changes($old, $new);

        $this->assertEquals(1, TicketUserWatcher::where('ticket_id', 1)->where('user_id', $newAssignee->id)->count());
    }

    #[Test]
    public function it_detects_due_date_change(): void
    {
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => '2024-01-01', 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => '2024-01-15', 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertNotEmpty(array_filter($changes, fn ($c) => str_contains($c, 'Due date changed')));
    }

    #[Test]
    public function it_detects_closed_at_change(): void
    {
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => '2024-01-15'];

        $changes = $this->service->changes($old, $new);

        $this->assertNotEmpty(array_filter($changes, fn ($c) => str_contains($c, 'Ticket closed')));
    }

    #[Test]
    public function it_returns_empty_array_when_nothing_changed(): void
    {
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = $old;

        $changes = $this->service->changes($old, $new);

        $this->assertEmpty($changes);
    }

    #[Test]
    public function it_detects_description_change(): void
    {
        $old = ['id' => 1, 'subject' => 'Test', 'description' => '', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];
        $new = ['id' => 1, 'subject' => 'Test', 'description' => 'Changed description', 'type_id' => 1, 'status_id' => 1, 'importance_id' => 1, 'milestone_id' => 1, 'project_id' => 1, 'estimate' => 0, 'user_id2' => 1, 'storypoints' => 0, 'due_at' => null, 'closed_at' => null];

        $changes = $this->service->changes($old, $new);

        $this->assertContains('Description changed to Changed description', $changes);
    }

    #[Test]
    public function it_creates_message_note_when_body_provided(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Auth::login($user);
        $this->service->notate($ticket->id, 'Test message', []);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Test message',
            'notetype' => 'message',
        ]);
    }

    #[Test]
    public function it_creates_changelog_note_when_changes_provided(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Auth::login($user);
        $this->service->notate($ticket->id, '', ['Subject changed']);

        $this->assertDatabaseHas('notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notetype' => 'changelog',
        ]);
    }

    #[Test]
    public function it_creates_both_notes_when_message_and_changes_provided(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Auth::login($user);
        $this->service->notate($ticket->id, 'Message', ['Change 1']);

        $this->assertEquals(2, Note::where('ticket_id', $ticket->id)->count());
    }

    #[Test]
    public function it_includes_hours_in_changelog_when_provided(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Auth::login($user);
        $this->service->notate($ticket->id, '', [], 5);

        $note = Note::where('ticket_id', $ticket->id)->where('notetype', 'changelog')->first();
        $this->assertStringContainsString('Time or Quantity adjusted by 5', $note->body);
    }

    #[Test]
    public function it_does_nothing_when_no_message_no_hours_no_changes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        Auth::login($user);
        $this->service->notate($ticket->id, '', [], 0);

        $this->assertEquals(0, Note::where('ticket_id', $ticket->id)->count());
    }

    #[Test]
    public function it_returns_all_lookup_arrays(): void
    {
        Type::factory()->create(['name' => 'Bug']);
        Importance::factory()->create(['name' => 'High']);
        Status::factory()->create(['name' => 'Open']);
        Project::factory()->create(['name' => 'Project A', 'active' => true]);
        Milestone::factory()->create(['name' => 'Sprint 1']);
        User::factory()->create(['name' => 'John']);
        Release::factory()->create(['title' => 'Release 1']);

        $lookups = $this->service->getLookups();

        $this->assertArrayHasKey('types', $lookups);
        $this->assertArrayHasKey('milestones', $lookups);
        $this->assertArrayHasKey('importances', $lookups);
        $this->assertArrayHasKey('projects', $lookups);
        $this->assertArrayHasKey('statuses', $lookups);
        $this->assertArrayHasKey('releases', $lookups);
        $this->assertArrayHasKey('users', $lookups);
    }

    #[Test]
    public function it_caches_lookups_for_60_minutes(): void
    {
        Cache::flush();
        Type::factory()->create();

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                return $key === 'ticket_lookups' && $ttl instanceof \DateTimeInterface;
            })
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service->getLookups();
    }

    #[Test]
    public function it_only_returns_active_projects(): void
    {
        Project::factory()->create(['name' => 'Active Project', 'active' => true]);
        Project::factory()->create(['name' => 'Inactive Project', 'active' => false]);

        $lookups = $this->service->getLookups();

        $this->assertTrue($lookups['projects']->contains('Active Project'));
        $this->assertFalse($lookups['projects']->has('Inactive Project'));
    }

    #[Test]
    public function it_only_returns_open_milestones(): void
    {
        Milestone::factory()->create(['name' => 'Open Milestone', 'end_at' => null]);
        Milestone::factory()->create(['name' => 'Closed Milestone', 'end_at' => now()]);

        $lookups = $this->service->getLookups();

        $this->assertTrue($lookups['milestones']->contains('Open Milestone'));
        $this->assertFalse($lookups['milestones']->has('Closed Milestone'));
    }

    #[Test]
    public function it_returns_lookups_sorted_by_name(): void
    {
        Type::factory()->create(['name' => 'Zebra']);
        Type::factory()->create(['name' => 'Apple']);
        Type::factory()->create(['name' => 'Banana']);

        $lookups = $this->service->getLookups();

        $this->assertEquals(['Apple', 'Banana', 'Zebra'], $lookups['types']->values()->toArray());
    }
}
