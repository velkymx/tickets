<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultsSeeder::class,
            UserSeeder::class,
            KbCategorySeeder::class,
            KbTagSeeder::class,
        ]);
    }
}
