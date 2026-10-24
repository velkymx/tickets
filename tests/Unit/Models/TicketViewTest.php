<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketViewTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_enforces_unique_constraint(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        TicketView::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);
        TicketView::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }
}
