<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use App\Services\PresenceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PresenceServiceTest extends TestCase
{
    use SeedsDatabase;

    private PresenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PresenceService;
    }

    #[Test]
    public function it_tracks_user_presence()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->service->updatePresence($ticket->id, $user);

        $viewers = $this->service->getViewers($ticket->id);

        $this->assertCount(1, $viewers);
        $this->assertEquals($user->id, $viewers[0]['user_id']);
        $this->assertEquals($user->name, $viewers[0]['name']);
    }

    #[Test]
    public function it_removes_expired_viewers()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->service->updatePresence($ticket->id, $user);

        $this->travel(31)->seconds();

        $viewers = $this->service->getViewers($ticket->id);
        $this->assertCount(0, $viewers);
    }
}
