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
    public function recent_tickets_groups_ownership_before_status_filter(): void
    {
        $user = User::factory()->create();

        $sql = Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->whereNotIn('status_id', [1, 2])->toSql();

        $this->assertStringContainsString('("user_id2" = ? or "user_id" = ?)', $sql);

        $controller = file_get_contents(app_path('Http/Controllers/TicketsController.php'));
        $this->assertStringContainsString('where(function ($q) use ($user)', $controller);
    }

    #[Test]
    public function home_shows_only_my_recent_tickets(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $open = Status::factory()->create(['name' => 'Open Now']);

        $mine = Ticket::factory()->create([
            'user_id2' => $user->id,
            'user_id' => $other->id,
            'status_id' => $open->id,
        ]);
        $theirs = Ticket::factory()->create([
            'user_id2' => $other->id,
            'user_id' => $other->id,
            'status_id' => $open->id,
        ]);

        $response = $this->actingAs($user)->get('/home');
        $response->assertStatus(200);
        $response->assertSee($mine->subject);
        $response->assertDontSee($theirs->subject);
    }
}
