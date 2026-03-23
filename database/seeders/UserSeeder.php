<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'noreply@ajbapps.com'],
            [
                'name' => 'Unassigned',
                'email' => 'noreply@ajbapps.com',
                'password' => Hash::make(str()->random(40)),
                'active' => 1,
                'admin' => 0,
                'title' => 'Unassigned User',
                'timezone' => 'UTC',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'support@ajbapps.com'],
            [
                'name' => 'administrator',
                'email' => 'support@ajbapps.com',
                'password' => Hash::make(config('app.admin_password', str()->random(40))),
                'active' => 1,
                'admin' => 1,
                'title' => 'Administrator User',
                'timezone' => 'UTC',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
