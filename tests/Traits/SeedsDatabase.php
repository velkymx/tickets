<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait SeedsDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    public function refreshDatabase(): void
    {
        $this->baseRefreshDatabase();

        $this->seed();
    }
}
