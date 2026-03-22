<?php

namespace Tests\Unit\Policies;

use App\Models\Release;
use App\Models\User;
use App\Policies\ReleasePolicy;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReleasePolicyTest extends TestCase
{
    use SeedsDatabase;

    protected ReleasePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ReleasePolicy;
    }

    #[Test]
    public function it_allows_any_user_to_view(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create();

        $this->assertTrue($this->policy->view($user, $release));
    }

    #[Test]
    public function it_allows_any_user_to_create(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function it_allows_owner_to_update(): void
    {
        $owner = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $release));
    }

    #[Test]
    public function it_denies_non_owner_from_updating(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => 999]);

        $this->assertFalse($this->policy->update($user, $release));
    }

    #[Test]
    public function it_allows_owner_to_delete(): void
    {
        $owner = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($owner, $release));
    }

    #[Test]
    public function it_denies_non_owner_from_deleting(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => 999]);

        $this->assertFalse($this->policy->delete($user, $release));
    }
}
