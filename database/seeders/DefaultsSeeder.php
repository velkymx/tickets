<?php

namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'importances' => [
                    '1' => 'trivial',
                    '2' => 'minor',
                    '3' => 'major',
                    '4' => 'critical',
                    '5' => 'blocker'
                    ],
    
            'milestones' => [
                    '1' => 'Unreviewed',
                    '2' => 'Future Backlog',
                    '3' => 'Backlog',
                    '4' => 'Scheduled',
                    ],
    
            'statuses' => [
                    '1' => 'new',
                    '2' => 'active',
                    '3' => 'testing',
                    '4' => 'ready to deploy',
                    '5' => 'completed',
                    '6' => 'waiting',
                    '7' => 'reopened',
                    '8' => 'duplicte',
                    '9' => 'declined'
                    ],
    
            'types' => [
                    '1' => 'bug',
                    '2' => 'enhancement',
                    '3' => 'task',
                    '4' => 'proposal'
            ]
    ];
    
        foreach($data as $table => $values){
    
          foreach($values as $key => $val){
    
          DB::table($table)->insert([
              'id' => $key,
              'name' => $val
          ]);
    
          }
    
          }
    
          DB::table('projects')->insert([
              'id' => 1,
              'name' => 'Unassigned',
              'description' => NULL,
              'active' => 1
          ]);
    
        }
    
}
