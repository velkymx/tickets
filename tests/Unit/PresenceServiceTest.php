<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\PresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PresenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private PresenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PresenceService();
    }

    #[Test]
    public function it_tracks_user_presence()
    {
        $user = User::factory()->create();
        $ticketId = 1;

        $this->service->updatePresence($ticketId, $user);

        $viewers = $this->service->getViewers($ticketId);

        $this->assertCount(1, $viewers);
        $this->assertEquals($user->id, $viewers[0]['user_id']);
        $this->assertEquals($user->name, $viewers[0]['name']);
    }

    #[Test]
    public function it_removes_expired_viewers()
    {
        $user = User::factory()->create();
        $ticketId = 1;

        $this->service->updatePresence($ticketId, $user);

        $this->travel(31)->seconds();

        $viewers = $this->service->getViewers($ticketId);
        $this->assertCount(0, $viewers);
    }
}
