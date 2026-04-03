<?php

namespace Tests\Unit\Models;

use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketEstimateTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $estimate = new TicketEstimate;
        $fillable = $estimate->getFillable();

        $this->assertContains('user_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
        $this->assertContains('storypoints', $fillable);
    }

    #[Test]
    public function it_casts_storypoints_to_integer(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $estimate = TicketEstimate::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'storypoints' => '8',
        ]);

        $this->assertIsInt($estimate->storypoints);
        $this->assertEquals(8, $estimate->storypoints);
    }

    #[Test]
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'Estimator']);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $estimate = TicketEstimate::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $estimate->user);
        $this->assertEquals('Estimator', $estimate->user->name);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Estimate Ticket',
        ]);
        $estimate = TicketEstimate::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $estimate->ticket);
        $this->assertEquals('Estimate Ticket', $estimate->ticket->subject);
    }
}
