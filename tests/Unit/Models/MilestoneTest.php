<?php

namespace Tests\Unit\Models;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\User;
use App\Notifications\WatcherNotification;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MilestoneTest extends TestCase
{
    use SeedsDatabase;

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
    public function it_always_notifies_watchers_even_without_eager_loading(): void
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

        Notification::assertSentTo($watcher, WatcherNotification::class);
        Notification::assertSentTo($owner, WatcherNotification::class);
    }
}
