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

        $this->seedDecisionRequests($owner, $project, $milestone, $typeId, $importanceId);
    }

    private function seedDecisionRequests(User $owner, Project $project, Milestone $milestone, ?int $typeId, ?int $importanceId): void
    {
        $lead = User::firstOrCreate(
            ['email' => 'demo-lead@ajbapps.com'],
            ['name' => 'Demo Lead', 'password' => Hash::make('password123'), 'active' => 1]
        );

        $requests = [
            ['subject' => 'Demo: pick the launch date', 'owner' => $owner, 'asker' => $lead, 'decider' => $owner, 'hours' => 2, 'question' => 'Launch on Tuesday or wait for the Friday release train?'],
            ['subject' => 'Demo: choose a payment provider', 'owner' => $owner, 'asker' => $lead, 'decider' => $owner, 'hours' => 30, 'question' => 'Stripe or Adyen for the first market?'],
            ['subject' => 'Demo: retire the legacy API', 'owner' => $lead, 'asker' => $lead, 'decider' => $owner, 'hours' => 80, 'question' => 'Can we switch off v1 at the end of the month?'],
            ['subject' => 'Demo: pick an icon set', 'owner' => $owner, 'asker' => $owner, 'decider' => $lead, 'hours' => 5, 'question' => 'Font Awesome solid or regular for the toolbar?'],
        ];

        foreach ($requests as $row) {
            $ticket = Ticket::firstOrCreate(
                ['subject' => $row['subject']],
                [
                    'description' => 'Demo ticket waiting on a decision.',
                    'type_id' => $typeId,
                    'status_id' => Status::byName('active')->value('id'),
                    'importance_id' => $importanceId,
                    'milestone_id' => $milestone->id,
                    'project_id' => $project->id,
                    'user_id' => $row['asker']->id,
                    'user_id2' => $row['owner']->id,
                ]
            );

            $note = Note::firstOrCreate(
                ['ticket_id' => $ticket->id, 'body' => $row['question']],
                [
                    'body_markdown' => $row['question'],
                    'user_id' => $row['asker']->id,
                    'decider_id' => $row['decider']->id,
                    'notetype' => 'decision_needed',
                    'hide' => 0,
                ]
            );

            if ($note->wasRecentlyCreated) {
                $note->forceFill(['created_at' => now()->subHours($row['hours'])])->save();
            }
        }

        $settled = Ticket::firstOrCreate(
            ['subject' => 'Demo: settled hosting question'],
            [
                'description' => 'Shows a decision request that has been answered.',
                'type_id' => $typeId,
                'status_id' => Status::byName('active')->value('id'),
                'importance_id' => $importanceId,
                'milestone_id' => $milestone->id,
                'project_id' => $project->id,
                'user_id' => $lead->id,
                'user_id2' => $owner->id,
            ]
        );

        $question = Note::firstOrCreate(
            ['ticket_id' => $settled->id, 'body' => 'Self-host or managed hosting?'],
            [
                'body_markdown' => 'Self-host or managed hosting?',
                'user_id' => $lead->id,
                'decider_id' => $owner->id,
                'notetype' => 'decision_needed',
                'resolved' => true,
                'resolved_by' => $owner->id,
                'resolution_message' => 'Managed hosting for year one',
                'hide' => 0,
            ]
        );

        Note::firstOrCreate(
            ['ticket_id' => $settled->id, 'parent_id' => $question->id, 'body' => 'Managed hosting for year one'],
            [
                'body_markdown' => 'Managed hosting for year one',
                'user_id' => $owner->id,
                'notetype' => 'decision',
                'hide' => 0,
            ]
        );

        Note::firstOrCreate(
            ['ticket_id' => $settled->id, 'body' => 'Should the staging box match production size?'],
            [
                'body_markdown' => 'Should the staging box match production size?',
                'user_id' => $lead->id,
                'notetype' => 'message',
                'hide' => 0,
            ]
        );
    }
}
