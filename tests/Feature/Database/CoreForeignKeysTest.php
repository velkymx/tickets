<?php

namespace Tests\Feature\Database;

use App\Models\Note;
use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class CoreForeignKeysTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function deleting_a_user_with_tickets_is_blocked(): void
    {
        $owner = User::factory()->create();
        Ticket::factory()->create(['user_id' => $owner->id]);

        $this->expectException(QueryException::class);

        $owner->delete();
    }

    #[Test]
    public function deleting_a_user_with_notes_is_blocked(): void
    {
        $author = User::factory()->create();
        Note::factory()->create(['user_id' => $author->id]);

        $this->expectException(QueryException::class);

        $author->delete();
    }

    #[Test]
    public function deleting_a_status_in_use_is_blocked(): void
    {
        $ticket = Ticket::factory()->create();

        $this->expectException(QueryException::class);

        Status::findOrFail($ticket->status_id)->delete();
    }

    #[Test]
    public function deleting_a_ticket_cascades_dependent_rows(): void
    {
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->create(['ticket_id' => $ticket->id]);
        $view = TicketView::factory()->create(['ticket_id' => $ticket->id]);
        $watcher = TicketUserWatcher::factory()->create(['ticket_id' => $ticket->id]);
        $estimate = TicketEstimate::factory()->create(['ticket_id' => $ticket->id]);
        ReleaseTicket::factory()->create(['ticket_id' => $ticket->id]);

        $ticket->delete();

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
        $this->assertDatabaseMissing('ticket_views', ['id' => $view->id]);
        $this->assertDatabaseMissing('ticket_user_watchers', ['id' => $watcher->id]);
        $this->assertDatabaseMissing('ticket_estimates', ['id' => $estimate->id]);
        $this->assertDatabaseMissing('release_tickets', ['ticket_id' => $ticket->id]);
    }

    #[Test]
    public function deleting_a_release_cascades_its_ticket_links(): void
    {
        $release = Release::factory()->create();
        $link = ReleaseTicket::factory()->create(['release_id' => $release->id]);

        $release->delete();

        $this->assertDatabaseMissing('release_tickets', ['id' => $link->id]);
        $this->assertDatabaseHas('tickets', ['id' => $link->ticket_id]);
    }
}
