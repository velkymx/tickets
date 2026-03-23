<?php

namespace Tests\Unit\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ProjectPolicyTest extends TestCase
{
    use SeedsDatabase;

    protected ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ProjectPolicy;
    }

    #[Test]
    public function it_allows_any_user_to_view(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertTrue($this->policy->view($user, $project));
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
        $project = Project::factory()->create();

        $this->assertTrue($this->policy->update($user, $project));
    }

    #[Test]
    public function it_denies_deleting_active_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['active' => true]);

        $this->assertFalse($this->policy->delete($user, $project));
    }

    #[Test]
    public function it_allows_deleting_inactive_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['active' => false]);

        $this->assertTrue($this->policy->delete($user, $project));
    }
}
