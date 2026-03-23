<?php

namespace Tests\Unit\Policies;

use App\Models\Milestone;
use App\Models\User;
use App\Policies\MilestonePolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class MilestonePolicyTest extends TestCase
{
    use SeedsDatabase;

    protected MilestonePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MilestonePolicy;
    }

    #[Test]
    public function it_allows_any_user_to_view(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->assertTrue($this->policy->view($user, $milestone));
    }

    #[Test]
    public function it_allows_any_user_to_view_any(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function it_allows_any_user_to_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function it_allows_scrummaster_to_update(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $milestone));
    }

    #[Test]
    public function it_denies_unrelated_user_from_updating(): void
    {
        $unrelated = User::factory()->create();
        $scrummaster = User::factory()->create();
        $owner = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => $scrummaster->id, 'owner_user_id' => $owner->id]);

        $this->assertFalse($this->policy->update($unrelated, $milestone));
    }

    #[Test]
    public function it_denies_unrelated_user_from_deleting(): void
    {
        $unrelated = User::factory()->create();
        $scrummaster = User::factory()->create();
        $owner = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => $scrummaster->id, 'owner_user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($unrelated, $milestone));
    }

    #[Test]
    public function it_denies_scrummaster_from_deleting(): void
    {
        $scrummaster = User::factory()->create();
        $owner = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => $scrummaster->id, 'owner_user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($scrummaster, $milestone));
    }

    #[Test]
    public function it_denies_anyone_from_updating_unassigned_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => null, 'owner_user_id' => null]);

        $this->assertTrue($this->policy->update($user, $milestone));
    }

    #[Test]
    public function it_denies_anyone_from_deleting_unassigned_milestone(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['scrummaster_user_id' => null, 'owner_user_id' => null]);

        $this->assertTrue($this->policy->delete($user, $milestone));
    }

    #[Test]
    public function it_allows_owner_to_delete(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create(['owner_user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $milestone));
    }

    #[Test]
    public function it_allows_any_user_to_view_report(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->assertTrue($this->policy->viewReport($user, $milestone));
    }

    #[Test]
    public function it_allows_any_user_to_watch(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->assertTrue($this->policy->watch($user, $milestone));
    }
}
