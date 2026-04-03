<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketUserWatcherTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $watcher = new TicketUserWatcher;
        $fillable = $watcher->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Watcher']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $watcher = TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $watcher->user);
        $this->assertEquals('Watcher', $watcher->user->name);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Watched Ticket',
        ]);
        $watcher = TicketUserWatcher::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $watcher->ticket);
        $this->assertEquals('Watched Ticket', $watcher->ticket->subject);
    }
}
