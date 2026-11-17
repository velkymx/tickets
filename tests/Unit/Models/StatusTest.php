<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class StatusTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function closed_status_ids_resolve_to_the_closed_names(): void
    {
        $names = Status::whereIn('id', Status::closedStatusIds())
            ->orderBy('id')
            ->pluck('name')
            ->all();

        $this->assertSame(['completed', 'duplicate', 'declined'], $names);
    }

    #[Test]
    public function closed_and_active_status_caches_refresh_on_create_update_and_delete(): void
    {
        $status = Status::factory()->create(['name' => 'in progress']);
        $this->assertNotContains($status->id, Status::closedStatusIds());
        $this->assertContains($status->id, Status::activeStatusIds());

        $status->update(['name' => 'resolved']);
        $this->assertContains($status->id, Status::closedStatusIds());
        $this->assertNotContains($status->id, Status::activeStatusIds());

        $status->update(['name' => 'in progress']);
        $this->assertNotContains($status->id, Status::closedStatusIds());
        $this->assertContains($status->id, Status::activeStatusIds());

        $status->delete();
        $this->assertNotContains($status->id, Status::activeStatusIds());
    }

    #[Test]
    public function cache_bust_waits_for_the_transaction_to_commit(): void
    {
        Cache::flush();
        $status = Status::factory()->create(['name' => 'in progress']);
        Status::activeStatusIds();

        DB::transaction(function () use ($status): void {
            $status->update(['name' => 'resolved']);
            $this->assertContains($status->id, Status::activeStatusIds());
        });

        $this->assertContains($status->id, Status::closedStatusIds());
        $this->assertNotContains($status->id, Status::activeStatusIds());
    }

    #[Test]
    public function deleting_a_closed_status_leaves_the_closed_bucket(): void
    {
        $status = Status::factory()->create(['name' => 'resolved']);
        $this->assertContains($status->id, Status::closedStatusIds());

        $status->delete();

        $this->assertNotContains($status->id, Status::closedStatusIds());
    }

    #[Test]
    public function active_status_ids_complete_the_full_set(): void
    {
        $all = Status::orderBy('id')->pluck('id')->all();

        $partition = collect([...Status::closedStatusIds(), ...Status::activeStatusIds()])
            ->sort()
            ->values()
            ->all();

        $this->assertSame($all, $partition);
    }

    #[Test]
    #[DataProvider('workflowStatuses')]
    public function is_closed_matches_the_workflow(int $statusId, bool $closed): void
    {
        $this->assertSame($closed, Status::isClosed($statusId));
    }

    public static function workflowStatuses(): array
    {
        return [
            'new' => [1, false],
            'active' => [2, false],
            'testing' => [3, false],
            'ready to deploy' => [4, false],
            'completed' => [5, true],
            'waiting' => [6, false],
            'reopened' => [7, false],
            'duplicate' => [8, true],
            'declined' => [9, true],
            'backlog' => [10, false],
        ];
    }
}
