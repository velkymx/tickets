<?php

namespace Tests\Feature\Tickets;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class HomeRecentTicketsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function home_shows_tickets_assigned_to_me(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $open = Status::factory()->create(['name' => 'Open Now']);

        $mine = Ticket::factory()->create([
            'user_id2' => $user->id,
            'user_id' => $other->id,
            'status_id' => $open->id,
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee($mine->subject);
    }

    #[Test]
    public function home_shows_tickets_i_created(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $open = Status::factory()->create(['name' => 'Open Now']);

        $created = Ticket::factory()->create([
            'user_id2' => $other->id,
            'user_id' => $user->id,
            'status_id' => $open->id,
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee($created->subject);
    }

    #[Test]
    public function home_hides_my_closed_tickets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $closed = Status::factory()->closed()->create();

        $assigned = Ticket::factory()->create([
            'user_id2' => $user->id,
            'user_id' => $other->id,
            'status_id' => $closed->id,
        ]);
        $created = Ticket::factory()->create([
            'user_id2' => $other->id,
            'user_id' => $user->id,
            'status_id' => $closed->id,
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertDontSee($assigned->subject);
        $response->assertDontSee($created->subject);
    }

    #[Test]
    public function home_hides_other_users_open_tickets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $open = Status::factory()->create(['name' => 'Open Now']);

        $theirs = Ticket::factory()->create([
            'user_id2' => $other->id,
            'user_id' => $other->id,
            'status_id' => $open->id,
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertDontSee($theirs->subject);
    }
}
