<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $importances = [
            1 => ['name' => 'trivial',  'class' => 'secondary', 'icon' => 'fa-solid fa-angle-down'],
            2 => ['name' => 'minor',    'class' => 'info',      'icon' => 'fa-solid fa-angles-down'],
            3 => ['name' => 'major',    'class' => 'warning',   'icon' => 'fa-solid fa-angle-up'],
            4 => ['name' => 'critical', 'class' => 'danger',    'icon' => 'fa-solid fa-angles-up'],
            5 => ['name' => 'blocker',  'class' => 'dark',      'icon' => 'fa-solid fa-fire'],
        ];

        foreach ($importances as $id => $attrs) {
            DB::table('importances')->updateOrInsert(['id' => $id], $attrs);
        }

        $data = [
            'statuses' => [
                1 => 'new',
                2 => 'active',
                3 => 'testing',
                4 => 'ready to deploy',
                5 => 'completed',
                6 => 'waiting',
                7 => 'reopened',
                8 => 'duplicate',
                9 => 'declined',
                10 => 'backlog',
            ],
            'types' => [
                1 => 'bug',
                2 => 'enhancement',
                3 => 'task',
                4 => 'proposal',
            ],
        ];

        foreach ($data as $table => $values) {
            foreach ($values as $key => $val) {
                if (! DB::table($table)->where('id', $key)->exists()) {
                    DB::table($table)->insert(['id' => $key, 'name' => $val]);
                }
            }
        }

        if (! DB::table('milestones')->where('id', 1)->exists()) {
            $milestones = [
                ['id' => 1, 'name' => 'Unreviewed'],
                ['id' => 2, 'name' => 'Future Backlog'],
                ['id' => 3, 'name' => 'Backlog'],
                ['id' => 4, 'name' => 'Scheduled'],
            ];
            DB::table('milestones')->insert($milestones);
        }

        if (! DB::table('projects')->where('id', 1)->exists()) {
            DB::table('projects')->insert([
                'id' => 1,
                'name' => 'Unassigned',
                'description' => null,
                'active' => 1,
            ]);
        }
    }
}
