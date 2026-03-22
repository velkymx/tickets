<?php

namespace App\Services;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Release;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    public function changes(array $old, array $new): array
    {
        $changes = ['subject', 'description', 'type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'estimate', 'user_id2', 'storypoints'];

        $lookups = $this->getLookups();

        $change_list = [];

        foreach ($changes as $change) {
            if (! array_key_exists($change, $new)) {
                continue;
            }

            if (array_key_exists($change, $old) && $old[$change] != $new[$change]) {

                $label = $change;

                if (substr($change, -3, 3) == '_id' || substr($change, -3, 3) == 'id2') {

                    $label = substr($change, 0, strlen($change) - 3);

                    $lookup = $label.'s';

                    if ($change == 'status_id') {
                        $lookup = 'statuses';
                    }

                    if ($change == 'storypoints') {
                        $label = 'Story points';
                    }

                    if ($change == 'user_id2') {
                        $lookup = 'users';
                        $label = 'Assigned user';

                        $watch = TicketUserWatcher::where('ticket_id', $old['id'])->where('user_id', $new[$change])->first();

                        if (! $watch) {
                            TicketUserWatcher::create(['user_id' => $new[$change], 'ticket_id' => $old['id']]);
                        }

                    }

                    $change_list[] = ucwords($label).' changed to '.$lookups[$lookup][$new[$change]];
                } else {
                    $change_list[] = ucwords($change).' changed to '.$new[$change];
                }
            }
        }

        $oldDueAt = $old['due_at'] ? Carbon::parse($old['due_at'])->timestamp : null;
        $newDueAt = array_key_exists('due_at', $new) && $new['due_at'] ? Carbon::parse($new['due_at'])->timestamp : null;
        if ($oldDueAt !== $newDueAt && ($oldDueAt !== null || $newDueAt !== null)) {
            $change_list[] = 'Due date changed to '.($new['due_at'] ? Carbon::parse($new['due_at'])->format('M jS, Y') : 'N/A');
        }

        $oldClosedAt = $old['closed_at'] ? Carbon::parse($old['closed_at'])->timestamp : null;
        $newClosedAt = array_key_exists('closed_at', $new) && $new['closed_at'] ? Carbon::parse($new['closed_at'])->timestamp : null;
        if ($oldClosedAt !== $newClosedAt && ($oldClosedAt !== null || $newClosedAt !== null)) {
            $change_list[] = 'Ticket closed on '.($new['closed_at'] ? Carbon::parse($new['closed_at'])->format('M jS, Y') : 'N/A');
        }

        return $change_list;
    }

    public function notate(int $ticketId, string $message, array $changes, int $addHours = 0): void
    {
        $hasMessage = strlen($message) > 0;
        $hasHours = $addHours > 0;
        $hasChanges = is_array($changes) && count($changes) > 0;

        if (! $hasMessage && ! $hasHours && ! $hasChanges) {
            return;
        }

        $insert = [
            'user_id' => Auth::id(),
            'ticket_id' => $ticketId,
            'body' => $message,
            'hours' => $addHours,
        ];

        $ticket = Ticket::findOrFail($ticketId);

        if (strlen($message) > 0) {
            $insert['notetype'] = 'message';

            Note::create($insert);
        }

        if ($addHours > 0) {
            $changes[] = 'Time or Quantity adjusted by '.$addHours;
        }

        if (is_array($changes) && count($changes) > 0) {

            $change_list = '';

            foreach ($changes as $change) {
                $change_list .= '<li>'.e($change).'</li>';
            }

            $insert['body'] = '<ul>'.$change_list.'</ul>';
            $insert['notetype'] = 'changelog';
            $insert['hours'] = 0;

            Note::create($insert);
        }
    }

    public function getLookups(): array
    {
        return Cache::remember('ticket_lookups', now()->addMinutes(60), function () {
            return [
                'types' => Type::orderBy('name')->pluck('name', 'id'),
                'milestones' => Milestone::orderBy('name')->where('end_at', null)->pluck('name', 'id'),
                'importances' => Importance::orderBy('name')->pluck('name', 'id'),
                'projects' => Project::orderBy('name')->where('active', 1)->pluck('name', 'id'),
                'statuses' => Status::orderBy('name')->pluck('name', 'id'),
                'releases' => Release::orderBy('title')->pluck('title', 'id'),
                'users' => User::orderBy('name')->pluck('name', 'id'),
            ];
        });
    }
}
