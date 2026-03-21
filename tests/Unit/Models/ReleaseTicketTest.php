<?php

namespace Tests\Unit\Models;

use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReleaseTicketTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $releaseTicket = new ReleaseTicket;
        $fillable = $releaseTicket->getFillable();

        $this->assertContains('release_id', $fillable);
        $this->assertContains('ticket_id', $fillable);
    }

    #[Test]
    public function it_belongs_to_ticket(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'subject' => 'Release Ticket Subject',
        ]);
        $releaseTicket = ReleaseTicket::factory()->create([
            'release_id' => $release->id,
            'ticket_id' => $ticket->id,
        ]);

        $this->assertInstanceOf(Ticket::class, $releaseTicket->ticket);
        $this->assertEquals('Release Ticket Subject', $releaseTicket->ticket->subject);
    }

    #[Test]
    public function it_belongs_to_release(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create([
            'user_id' => $user->id,
            'title' => 'Release Title',
        ]);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        $releaseTicket = ReleaseTicket::factory()->create([
            'release_id' => $release->id,
            'ticket_id' => $ticket->id,
        ]);

        $this->assertInstanceOf(Release::class, $releaseTicket->release);
        $this->assertEquals('Release Title', $releaseTicket->release->title);
    }
}
