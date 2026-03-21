<?php

namespace Tests\Unit\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TicketPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TicketPolicy;
    }

    #[Test]
    public function it_allows_any_user_to_view_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function it_allows_any_user_to_view_any_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->view($user, $ticket));
    }

    #[Test]
    public function it_allows_any_user_to_create_tickets(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function it_allows_ticket_owner_to_update(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->update($owner, $ticket));
    }

    #[Test]
    public function it_allows_ticket_assignee_to_update(): void
    {
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => 999, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->update($assignee, $ticket));
    }

    #[Test]
    public function it_denies_unrelated_user_from_updating(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => 999, 'user_id2' => 998]);

        $this->assertFalse($this->policy->update($user, $ticket));
    }

    #[Test]
    public function it_allows_only_owner_to_delete(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->delete($owner, $ticket));
    }

    #[Test]
    public function it_denies_assignee_from_deleting(): void
    {
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => 999, 'user_id2' => $assignee->id]);

        $this->assertFalse($this->policy->delete($assignee, $ticket));
    }

    #[Test]
    public function it_denies_unrelated_user_from_deleting(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => 999, 'user_id2' => 998]);

        $this->assertFalse($this->policy->delete($user, $ticket));
    }

    #[Test]
    public function it_allows_any_user_to_claim(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->claim($user, $ticket));
    }

    #[Test]
    public function it_allows_any_user_to_estimate(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->estimate($user, $ticket));
    }

    #[Test]
    public function it_allows_any_user_to_add_note(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->addNote($user, $ticket));
    }
}
