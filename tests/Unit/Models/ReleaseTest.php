<?php

namespace Tests\Unit\Models;

use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Ticket;
use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReleaseTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $release = new Release;
        $fillable = $release->getFillable();

        $this->assertContains('title', $fillable);
        $this->assertContains('body', $fillable);
        $this->assertContains('started_at', $fillable);
        $this->assertContains('completed_at', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    #[Test]
    public function it_has_many_tickets(): void
    {
        $user = User::factory()->create();
        $release = Release::factory()->create(['user_id' => $user->id]);
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'user_id2' => $user->id,
        ]);
        ReleaseTicket::factory()->create([
            'release_id' => $release->id,
            'ticket_id' => $ticket->id,
        ]);

        $this->assertCount(1, $release->tickets);
        $this->assertInstanceOf(ReleaseTicket::class, $release->tickets->first());
    }

    #[Test]
    public function it_belongs_to_owner(): void
    {
        $user = User::factory()->create(['name' => 'Release Owner']);
        $release = Release::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $release->owner);
        $this->assertEquals('Release Owner', $release->owner->name);
    }
}
