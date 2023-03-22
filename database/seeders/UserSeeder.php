<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            
            'name' => 'Unassigned',
            'email' => '',
            'password' => Hash::make(rand()),            
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'active' => 1,
            'title' => 'Unassigned User',     
            'timezone' => 'UTC',     

        ]);        
    }
}
