<?php

namespace Tests\Unit\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketPolicyTest extends TestCase
{
    use SeedsDatabase;

    protected TicketPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TicketPolicy;
    }

    #[Test]
    public function read_create_and_note_abilities_are_open_to_any_user(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $ticket));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->addNote($user, $ticket));
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
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->update($assignee, $ticket));
    }

    #[Test]
    public function it_denies_unrelated_user_from_updating(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertFalse($this->policy->update($unrelated, $ticket));
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
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertFalse($this->policy->delete($assignee, $ticket));
    }

    #[Test]
    public function it_denies_unrelated_user_from_deleting(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->delete($unrelated, $ticket));
    }

    #[Test]
    public function it_allows_claiming_unassigned_tickets(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => 0]);

        $this->assertTrue($this->policy->claim($user, $ticket));
    }

    #[Test]
    public function it_denies_claiming_already_assigned_tickets(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertFalse($this->policy->claim($unrelated, $ticket));
    }

    #[Test]
    public function it_allows_owner_to_claim_own_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->claim($owner, $ticket));
    }

    #[Test]
    public function it_allows_owner_to_estimate(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->estimate($owner, $ticket));
    }

    #[Test]
    public function it_allows_assignee_to_estimate(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->estimate($assignee, $ticket));
    }

    #[Test]
    public function it_denies_unrelated_user_from_estimating(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertFalse($this->policy->estimate($unrelated, $ticket));
    }

    #[Test]
    public function it_allows_admin_full_access(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        // before() is called via Gate, not direct method call
        $this->assertTrue($admin->can('view', $ticket));
        $this->assertTrue($admin->can('update', $ticket));
        $this->assertTrue($admin->can('delete', $ticket));
        $this->assertTrue($admin->can('addNote', $ticket));
    }
}
