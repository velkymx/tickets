<?php

namespace Tests\Unit\Migrations;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ReleasesTableMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function releases_migration_down_drops_correct_table(): void
    {
        $this->assertTrue(Schema::hasTable('releases'));

        // Run the down migration via artisan
        (new \CreateReleasesTable)->down();

        $this->assertFalse(Schema::hasTable('releases'));
    }
}
