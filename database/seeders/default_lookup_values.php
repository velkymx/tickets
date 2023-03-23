<?php

use Illuminate\Database\Seeder;

class default_lookup_values extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
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
