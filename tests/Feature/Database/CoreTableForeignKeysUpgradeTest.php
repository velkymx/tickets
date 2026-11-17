<?php

namespace Tests\Feature\Database;

use AddCoreTableForeignKeys;
use App\Models\Note;
use App\Models\ReleaseTicket;
use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

require_once __DIR__.'/../../../database/migrations/2026_11_18_000001_add_core_table_foreign_keys.php';

class CoreTableForeignKeysUpgradeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function migrating_legacy_rows_backfills_and_constrains(): void
    {
        (new AddCoreTableForeignKeys)->down();

        $owner = User::factory()->create();
        $legacy = Ticket::factory()->create([
            'user_id' => $owner->id,
            'user_id2' => 0,
            'importance_id' => 0,
            'milestone_id' => 0,
            'project_id' => 0,
        ]);
        $orphanNote = Note::factory()->create(['ticket_id' => 999999]);
        $orphanView = TicketView::factory()->create(['ticket_id' => 999999]);
        $orphanWatcher = TicketUserWatcher::factory()->create(['ticket_id' => 999999]);
        $orphanEstimate = TicketEstimate::factory()->create(['ticket_id' => 999999]);
        $orphanLink = ReleaseTicket::factory()->create(['ticket_id' => 999999]);

        (new AddCoreTableForeignKeys)->up();

        $legacy->refresh();
        $this->assertNull($legacy->user_id2);
        $this->assertSame(1, $legacy->importance_id);
        $this->assertSame(1, $legacy->milestone_id);
        $this->assertSame(1, $legacy->project_id);
        $this->assertDatabaseMissing('notes', ['id' => $orphanNote->id]);
        $this->assertDatabaseMissing('ticket_views', ['id' => $orphanView->id]);
        $this->assertDatabaseMissing('ticket_user_watchers', ['id' => $orphanWatcher->id]);
        $this->assertDatabaseMissing('ticket_estimates', ['id' => $orphanEstimate->id]);
        $this->assertDatabaseMissing('release_tickets', ['id' => $orphanLink->id]);

        $legacy->delete();
        $owner->delete();
    }

    #[Test]
    public function migrating_with_orphaned_authors_aborts_before_constraints(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        Schema::disableForeignKeyConstraints();
        DB::table('users')->where('id', $owner->id)->delete();
        Schema::enableForeignKeyConstraints();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tickets owned by deleted users: ['.$ticket->id.']');

        (new AddCoreTableForeignKeys)->up();
    }
}
