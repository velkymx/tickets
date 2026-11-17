<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $unassigned = User::updateOrCreate(
            ['email' => 'noreply@ajbapps.com'],
            [
                'name' => 'Unassigned',
                'password' => Hash::make(str()->random(40)),
                'timezone' => 'UTC',
                'title' => 'Unassigned User',
            ]
        );
        $unassigned->forceFill(['active' => 1, 'admin' => 0])->saveQuietly();

        $admin = User::updateOrCreate(
            ['email' => 'support@ajbapps.com'],
            [
                'name' => 'administrator',
                'password' => Hash::make('password123'),
                'timezone' => 'UTC',
                'title' => 'Administrator User',
            ]
        );
        $admin->forceFill(['active' => 1, 'admin' => 1])->saveQuietly();
    }
}
