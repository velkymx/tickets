<?php

namespace Tests\Unit\Policies;

use App\Models\Milestone;
use App\Models\User;
use App\Policies\MilestonePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MilestonePolicyTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_allows_any_user_to_update(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->assertTrue($this->policy->update($user, $milestone));
    }

    #[Test]
    public function it_allows_any_user_to_delete(): void
    {
        $user = User::factory()->create();
        $milestone = Milestone::factory()->create();

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
