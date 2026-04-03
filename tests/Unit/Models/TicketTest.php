<?php

namespace Tests\Unit\Models;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\Type;
use App\Models\User;
use App\Notifications\WatcherNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketTest extends TestCase
{
    use SeedsDatabase;

    protected Status $openStatus;

    protected Status $closedStatus;

    protected Type $type;

    protected Importance $importance;

    protected Project $project;

    protected Milestone $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openStatus = Status::factory()->create(['name' => 'New']);
        $this->closedStatus = Status::factory()->closed()->create();
        $this->type = Type::factory()->create(['name' => 'Bug']);
        $this->importance = Importance::factory()->create(['name' => 'Critical']);
        $this->project = Project::factory()->create(['name' => 'Test Project']);
        $this->milestone = Milestone::factory()->create(['name' => 'Sprint 1']);
    }

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $ticket = new Ticket;
        $fillable = $ticket->getFillable();

        $this->assertContains('subject', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('type_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('status_id', $fillable);
        $this->assertContains('importance_id', $fillable);
        $this->assertContains('milestone_id', $fillable);
        $this->assertContains('project_id', $fillable);
        $this->assertContains('user_id2', $fillable);
        $this->assertContains('due_at', $fillable);
        $this->assertContains('closed_at', $fillable);
        $this->assertContains('estimate', $fillable);
        $this->assertContains('storypoints', $fillable);
    }

    #[Test]
    public function it_casts_due_at_to_carbon(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'due_at' => '2024-01-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $ticket->due_at);
        $this->assertEquals('2024-01-15', $ticket->due_at->format('Y-m-d'));
    }

    #[Test]
    public function it_casts_closed_at_to_carbon(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'closed_at' => '2024-01-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $ticket->closed_at);
        $this->assertEquals('2024-01-15', $ticket->closed_at->format('Y-m-d'));
    }

    #[Test]
    public function it_casts_estimate_to_decimal(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'estimate' => '8.50',
        ]);

        $this->assertEquals('8.50', $ticket->estimate);
    }

    #[Test]
    public function it_casts_storypoints_to_integer(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'storypoints' => '5',
        ]);

        $this->assertIsInt($ticket->storypoints);
        $this->assertEquals(5, $ticket->storypoints);
    }

    #[Test]
    public function it_casts_actual_to_integer(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'actual' => '10',
        ]);

        $this->assertIsInt($ticket->actual);
        $this->assertEquals(10, $ticket->actual);
    }

    #[Test]
    public function it_belongs_to_type(): void
    {
        $user = User::factory()->create();
        $type = Type::factory()->create(['name' => 'Feature']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'type_id' => $type->id,
        ]);

        $this->assertInstanceOf(Type::class, $ticket->type);
        $this->assertEquals('Feature', $ticket->type->name);
    }

    #[Test]
    public function it_belongs_to_status(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => $this->openStatus->id,
        ]);

        $this->assertInstanceOf(Status::class, $ticket->status);
        $this->assertEquals('New', $ticket->status->name);
    }

    #[Test]
    public function it_belongs_to_importance(): void
    {
        $user = User::factory()->create();
        $importance = Importance::factory()->create(['name' => 'High']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'importance_id' => $importance->id,
        ]);

        $this->assertInstanceOf(Importance::class, $ticket->importance);
        $this->assertEquals('High', $ticket->importance->name);
    }

    #[Test]
    public function it_belongs_to_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'My Project']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Project::class, $ticket->project);
        $this->assertEquals('My Project', $ticket->project->name);
    }

    #[Test]
    public function it_belongs_to_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Sprint 2']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'milestone_id' => $milestone->id,
        ]);

        $this->assertInstanceOf(Milestone::class, $ticket->milestone);
        $this->assertEquals('Sprint 2', $ticket->milestone->name);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Creator']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => User::factory()->create()->id,
        ]);

        $this->assertInstanceOf(User::class, $ticket->user);
        $this->assertEquals('Creator', $ticket->user->name);
    }

    #[Test]
    public function it_belongs_to_assignee(): void
    {
        $user = User::factory()->create(['name' => 'Assignee']);
        $creator = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $creator->id,
            'user_id2' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $ticket->assignee);
        $this->assertEquals('Assignee', $ticket->assignee->name);
    }

    #[Test]
    public function it_has_many_notes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        Note::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(3, $ticket->notes);
        $this->assertInstanceOf(Collection::class, $ticket->notes);
    }

    #[Test]
    public function it_has_many_estimates(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        TicketEstimate::factory()->count(2)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertCount(2, $ticket->estimates);
        $this->assertInstanceOf(Collection::class, $ticket->estimates);
    }

    #[Test]
    public function it_has_many_watchers(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        TicketUserWatcher::factory()->count(2)->create([
            'ticket_id' => $ticket->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertCount(2, $ticket->watchers);
        $this->assertInstanceOf(Collection::class, $ticket->watchers);
    }

    #[Test]
    public function it_has_many_views(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        TicketView::factory()->count(3)->create([
            'ticket_id' => $ticket->id,
        ]);

        $this->assertCount(3, $ticket->views);
    }

    #[Test]
    public function it_computes_actual_hours_from_notes_sum(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 2.5,
        ]);
        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 3.5,
        ]);

        $loadedTicket = Ticket::withSum('notes', 'hours')->find($ticket->id);
        $this->assertEquals(6, $loadedTicket->actual_hours);
    }

    #[Test]
    public function it_returns_zero_actual_hours_when_not_eager_loaded(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);

        Note::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'hours' => 2,
        ]);

        $this->assertEquals(0, $ticket->actual_hours);
    }

    #[Test]
    public function it_notifies_watchers_on_update(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $watcher = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $creator->id,
            'user_id2' => $creator->id,
        ]);

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $watcher->id,
        ]);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertSentTo($watcher, WatcherNotification::class);
    }

    #[Test]
    public function it_does_not_notify_the_acting_user_on_update(): void
    {
        Notification::fake();

        $actor = User::factory()->create();
        $otherWatcher = User::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_id' => $actor->id,
            'user_id2' => $actor->id,
        ]);

        // Both the actor and another user are watching
        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
        ]);
        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherWatcher->id,
        ]);

        $this->actingAs($actor);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertSentTo($otherWatcher, WatcherNotification::class);
        Notification::assertNotSentTo($actor, WatcherNotification::class);
    }

    #[Test]
    public function it_skips_watchers_with_no_user(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $creator->id,
            'user_id2' => $creator->id,
        ]);

        TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => 99999,
        ]);

        $ticket = $ticket->fresh()->load('watchers');
        $ticket->subject = 'Updated Subject';
        $ticket->save();

        Notification::assertNothingSent();
    }
}
