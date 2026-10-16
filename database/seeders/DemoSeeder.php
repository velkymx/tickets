<?php

namespace Database\Seeders;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DefaultsSeeder::class);

        // Known-credential demo account: local and test runs only, never
        // seeded in production or staging.
        if (! in_array(app()->environment(), ['local', 'testing'], true)) {
            $this->command?->warn('DemoSeeder only seeds demo content on local and testing environments.');

            return;
        }

        $owner = User::firstOrCreate(
            ['email' => 'demo@ajbapps.com'],
            ['name' => 'Demo Agent', 'password' => Hash::make('password123'), 'active' => 1]
        );

        $project = Project::firstOrCreate(
            ['name' => 'Demo Sandbox'],
            ['description' => 'Safe playground for agents. Mutate freely.', 'active' => 1]
        );

        $milestone = Milestone::firstOrCreate(
            ['name' => 'Demo Sprint'],
            ['description' => 'Demo milestone for agent walkthroughs.']
        );

        $typeId = Type::where('name', 'task')->value('id');
        $importanceId = Importance::where('name', 'major')->value('id');

        $tickets = [
            [
                'subject' => 'Demo: blocked checkout flow',
                'description' => 'Waiting on the payments stub.',
                'status' => 'active',
                'notes' => [
                    ['body' => 'Demo: payments stub blocks checkout', 'notetype' => 'blocker'],
                    ['body' => 'Demo: ship checklist', 'notetype' => 'action'],
                ],
            ],
            [
                'subject' => 'Demo: open onboarding copy',
                'description' => 'Needs friendlier empty states.',
                'status' => 'new',
                'notes' => [
                    ['body' => 'Demo: keep tone short and plain', 'notetype' => 'decision'],
                ],
            ],
            [
                'subject' => 'Demo: finished import mapping',
                'description' => 'CSV columns mapped and verified.',
                'status' => 'completed',
                'notes' => [],
            ],
        ];

        foreach ($tickets as $row) {
            $ticket = Ticket::firstOrCreate(
                ['subject' => $row['subject']],
                [
                    'description' => $row['description'],
                    'type_id' => $typeId,
                    'status_id' => Status::byName($row['status'])->value('id'),
                    'importance_id' => $importanceId,
                    'milestone_id' => $milestone->id,
                    'project_id' => $project->id,
                    'user_id' => $owner->id,
                    'user_id2' => $owner->id,
                ]
            );

            foreach ($row['notes'] as $note) {
                Note::firstOrCreate(
                    ['ticket_id' => $ticket->id, 'body' => $note['body']],
                    [
                        'body_markdown' => $note['body'],
                        'user_id' => $owner->id,
                        'notetype' => $note['notetype'],
                        'hide' => 0,
                    ]
                );
            }
        }
    }
}
