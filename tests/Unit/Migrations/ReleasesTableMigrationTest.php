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
    public function releases_table_exists_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('releases'));
        $this->assertTrue(Schema::hasColumns('releases', [
            'id', 'title', 'body', 'started_at', 'completed_at',
            'user_id', 'created_at', 'updated_at',
        ]));
    }
}
