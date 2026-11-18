<?php

namespace Tests\Traits;

use Database\Seeders\DefaultsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait SeedsDatabase
{
    use RefreshDatabase {
        refreshDatabase as baseRefreshDatabase;
    }

    public function refreshDatabase(): void
    {
        $this->baseRefreshDatabase();

        $this->seed([
            DefaultsSeeder::class,
            UserSeeder::class,
        ]);
    }
}
