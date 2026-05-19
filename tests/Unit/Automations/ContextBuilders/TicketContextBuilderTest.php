<?php

namespace Tests\Unit\Automations\ContextBuilders;

use App\Automations\ContextBuilders\TicketCreatedContextBuilder;
use App\Automations\ContextBuilders\TicketUpdatedContextBuilder;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketContextBuilderTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function ticket_created_builder_returns_ticket_context(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $event = new TicketCreated($ticket, $user->id);
        $builder = new TicketCreatedContextBuilder();
        $context = $builder->build($event);

        $this->assertArrayHasKey('ticket', $context);
        $this->assertEquals($ticket->id, $context['ticket']['id']);
        $this->assertEquals($ticket->subject, $context['ticket']['subject']);
        $this->assertArrayHasKey('actor', $context);
        $this->assertEquals($user->id, $context['actor']['id']);
    }

    #[Test]
    public function ticket_updated_builder_includes_changes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $event = new TicketUpdated($ticket, ['status_id' => 5], $user->id);
        $builder = new TicketUpdatedContextBuilder();
        $context = $builder->build($event);

        $this->assertArrayHasKey('changes', $context);
        $this->assertArrayHasKey('status_id', $context['changes']);
    }

    #[Test]
    public function created_builder_returns_empty_changes(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $event = new TicketCreated($ticket, $user->id);
        $context = (new TicketCreatedContextBuilder())->build($event);

        $this->assertArrayHasKey('changes', $context);
        $this->assertEmpty($context['changes']);
    }
}
