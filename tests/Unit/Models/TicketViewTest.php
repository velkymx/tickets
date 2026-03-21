<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketViewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $view = new TicketView;
        $fillable = $view->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Viewer']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $view = TicketView::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $view->user);
        $this->assertEquals('Viewer', $view->user->name);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Viewed Ticket',
        ]);
        $view = TicketView::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $view->ticket);
        $this->assertEquals('Viewed Ticket', $view->ticket->subject);
    }

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
