<?php

namespace Tests\Feature\Tickets;

use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class TicketShowOrphanedUsersTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function show_renders_when_viewer_and_watcher_were_deleted(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $viewer = User::factory()->create();

        TicketView::create(['user_id' => $viewer->id, 'ticket_id' => $ticket->id]);
        TicketUserWatcher::create(['user_id' => $viewer->id, 'ticket_id' => $ticket->id]);

        DB::table('users')->where('id', $viewer->id)->delete();

        $this->actingAs($owner)->get("/tickets/{$ticket->id}")->assertOk();
    }

    #[Test]
    public function deleting_user_cleans_up_views_and_watchers(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);
        $viewer = User::factory()->create();

        TicketView::create(['user_id' => $viewer->id, 'ticket_id' => $ticket->id]);
        TicketUserWatcher::create(['user_id' => $viewer->id, 'ticket_id' => $ticket->id]);

        $viewer->delete();

        $this->assertDatabaseMissing('ticket_views', ['user_id' => $viewer->id]);
        $this->assertDatabaseMissing('ticket_user_watchers', ['user_id' => $viewer->id]);
    }
}
