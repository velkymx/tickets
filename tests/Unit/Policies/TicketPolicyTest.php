<?php

namespace Tests\Unit\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
    public function it_allows_any_user_to_view_tickets(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function it_allows_owner_to_view_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($owner, $ticket));
    }

    #[Test]
    public function it_allows_assignee_to_view_ticket(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->view($assignee, $ticket));
    }

    #[Test]
    public function it_allows_unrelated_user_to_view(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->view($unrelated, $ticket));
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
    public function it_allows_owner_to_add_note(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->addNote($owner, $ticket));
    }

    #[Test]
    public function it_allows_assignee_to_add_note(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->addNote($assignee, $ticket));
    }

    #[Test]
    public function it_allows_unrelated_user_to_add_note(): void
    {
        $unrelated = User::factory()->create();
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id, 'user_id2' => $assignee->id]);

        $this->assertTrue($this->policy->addNote($unrelated, $ticket));
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
