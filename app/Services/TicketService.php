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
use App\Notifications\WatcherNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TicketService
{
    public function __construct(
        private ?SlashCommandService $slashCommandService = null,
        private ?MarkdownService $markdownService = null,
        private ?MentionService $mentionService = null,
    ) {
        $this->slashCommandService ??= app(SlashCommandService::class);
        $this->markdownService ??= app(MarkdownService::class);
        $this->mentionService ??= app(MentionService::class);
    }

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
        $ticket = Ticket::findOrFail($ticketId);
        $commandResults = $this->slashCommandService->handle($ticket, $message);

        $message = $commandResults['body'] ?? trim($message);
        $changes = array_merge($changes, $commandResults['changes'] ?? []);
        $addHours += (int) ($commandResults['hours'] ?? 0);
        $noteType = $commandResults['note_type'] ?? 'message';
        $noteAttributes = $commandResults['note_attributes'] ?? [];

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
        $createdActivity = false;

        if (strlen($message) > 0) {
            $insert['body_markdown'] = $message;
            $insert['body'] = $this->markdownService->parse($message);
            $insert['notetype'] = $noteType;
            $insert = array_merge($insert, $noteAttributes);

            $note = Note::create($insert);
            $this->createMentions($note, $message);
            $createdActivity = true;
        }

        if ($addHours > 0) {
            $changes[] = 'Time or Quantity adjusted by '.$addHours;
        }

        if (is_array($changes) && count($changes) > 0) {

            $insert['body_markdown'] = collect($changes)
                ->map(fn (string $change) => '- '.$change)
                ->implode("\n");
            $insert['body'] = $this->markdownService->parse($insert['body_markdown']);
            $insert['notetype'] = 'changelog';
            $insert['hours'] = 0;

            Note::create($insert);
            $createdActivity = true;
        }

        if ($createdActivity) {
            $this->notifyWatchers($ticket);
        }
    }

    private function createMentions(Note $note, string $markdown): void
    {
        $usernames = $this->mentionService->parseMentions($markdown);

        if ($usernames === []) {
            return;
        }

        $userIds = User::query()
            ->whereIn('name', $usernames)
            ->pluck('id')
            ->all();

        $this->mentionService->createMentions($note, $userIds);
    }

    private function notifyWatchers(Ticket $ticket): void
    {
        $url = url("/tickets/{$ticket->id}");
        $message = "The Ticket '{$ticket->subject}' has been updated.";
        $exceptUserId = Auth::id();

        $ticket->load('watchers.user');

        $ticket->watchers->each(function ($watcher) use ($message, $url, $exceptUserId) {
            if ($watcher->user_id !== $exceptUserId && $watcher->user?->email) {
                $watcher->user->notify(new WatcherNotification('Ticket', $message, $url));
            }
        });
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
