<?php

namespace Tests\Unit\Models;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MilestoneWatcherTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $watcher = new MilestoneWatcher;
        $fillable = $watcher->getFillable();

        $this->assertContains('milestone_id', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Milestone Watcher']);
        $milestone = Milestone::factory()->create();
        $watcher = MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $watcher->user);
        $this->assertEquals('Milestone Watcher', $watcher->user->name);
    }

    #[Test]
    public function it_belongs_to_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['name' => 'Sprint 5']);
        $watcher = MilestoneWatcher::factory()->create([
            'milestone_id' => $milestone->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Milestone::class, $watcher->milestone);
        $this->assertEquals('Sprint 5', $watcher->milestone->name);
    }
}
