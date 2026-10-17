<?php

namespace Tests\Unit\Models;

use App\Models\Status;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketScopesTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function open_scope_excludes_closed_tickets(): void
    {
        $open = Status::factory()->create(['name' => 'Open Scope']);
        $closed = Status::factory()->closed()->create();

        $openTicket = Ticket::factory()->create(['status_id' => $open->id]);
        $closedTicket = Ticket::factory()->create(['status_id' => $closed->id]);

        $ids = Ticket::open()->pluck('id')->all();

        $this->assertContains($openTicket->id, $ids);
        $this->assertNotContains($closedTicket->id, $ids);
    }

    #[Test]
    public function closed_scope_returns_only_closed_tickets(): void
    {
        $open = Status::factory()->create(['name' => 'Open Scope Two']);
        $closed = Status::factory()->closed()->create();

        $openTicket = Ticket::factory()->create(['status_id' => $open->id]);
        $closedTicket = Ticket::factory()->create(['status_id' => $closed->id]);

        $ids = Ticket::closed()->pluck('id')->all();

        $this->assertContains($closedTicket->id, $ids);
        $this->assertNotContains($openTicket->id, $ids);
    }
}
