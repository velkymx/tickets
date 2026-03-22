<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketUserWatchersMutedMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_muted_column_with_a_false_default(): void
    {
        $this->assertTrue(Schema::hasColumn('ticket_user_watchers', 'muted'));

        $columns = collect(DB::select('PRAGMA table_info(ticket_user_watchers)'))->keyBy('name');

        $this->assertContains((string) $columns['muted']->dflt_value, ['0', "'0'"]);
    }
}
