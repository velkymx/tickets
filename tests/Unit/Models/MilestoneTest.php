<?php

namespace Tests\Unit\Models;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\WatcherNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $milestone = new Milestone;
        $fillable = $milestone->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('scrummaster_user_id', $fillable);
        $this->assertContains('owner_user_id', $fillable);
        $this->assertContains('start_at', $fillable);
        $this->assertContains('due_at', $fillable);
        $this->assertContains('end_at', $fillable);
    }

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $milestone = new Milestone;

        $this->assertFalse($milestone->timestamps);
    }

    #[Test]
    public function it_has_many_tickets(): void
    {
        $owner = User::factory()->create();
        $milestone = Milestone::factory()->create(['owner_user_id' => $owner->id]);
        $ticket = Ticket::factory()->create(['milestone_id' => $milestone->id]);

        $this->assertCount(1, $milestone->tickets);
        $this->assertTrue($milestone->tickets->contains($ticket));
    }

    #[Test]
    public function it_belongs_to_owner(): void
    {
        $owner = User::factory()->create(['name' => 'Milestone Owner']);
        $milestone = Milestone::factory()->create(['owner_user_id' => $owner->id]);

        $this->assertInstanceOf(User::class, $milestone->owner);
        $this->assertEquals('Milestone Owner', $milestone->owner->name);
    }

    #[Test]
    public function it_belongs_to_scrummaster(): void
    {
        $scrummaster = User::factory()->create(['name' => 'Scrum Master']);
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => $scrummaster->id]);

        $this->assertInstanceOf(User::class, $milestone->scrummaster);
        $this->assertEquals('Scrum Master', $milestone->scrummaster->name);
    }

    #[Test]
    public function it_has_many_watchers(): void
    {
        $owner = User::factory()->create();
        $milestone = Milestone::factory()->create(['owner_user_id' => $owner->id]);
        $watcher = User::factory()->create();
        MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $watcher->id,
        ]);

        $this->assertCount(1, $milestone->watchers);
        $this->assertInstanceOf(MilestoneWatcher::class, $milestone->watchers->first());
    }

    #[Test]
    public function it_notifies_watchers_on_update(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $scrummaster = User::factory()->create();
        $watcher = User::factory()->create();

        $milestone = Milestone::factory()->create([
            'owner_user_id' => $owner->id,
            'scrummaster_user_id' => $scrummaster->id,
        ]);

        MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $watcher->id,
        ]);

        $milestone = $milestone->fresh()->load(['owner', 'scrummaster', 'watchers']);
        $milestone->name = 'Updated Name';
        $milestone->save();

        Notification::assertSentTo($owner, WatcherNotification::class);
        Notification::assertSentTo($scrummaster, WatcherNotification::class);
        Notification::assertSentTo($watcher, WatcherNotification::class);
    }

    #[Test]
    public function it_notifies_only_loaded_watchers(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $watcher = User::factory()->create();

        $milestone = Milestone::factory()->create(['owner_user_id' => $owner->id]);

        MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $watcher->id,
        ]);

        $milestone = $milestone->fresh()->load(['owner', 'scrummaster']);
        $milestone->name = 'Updated Without Loading Watchers';
        $milestone->save();

        Notification::assertNotSentTo($watcher, WatcherNotification::class);
        Notification::assertSentTo($owner, WatcherNotification::class);
    }
}
