<?php

namespace App\Services;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\ValueObjects\TicketPulse;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TicketPulseService
{
    public function getPulse(Ticket $ticket): TicketPulse
    {
        $pulse = Cache::remember("ticket_pulse:{$ticket->id}", now()->addHour(), function () use ($ticket) {
            return $this->computePulse($ticket);
        });

        // Apply Auth-dependent personalization outside cache
        if (Auth::id() !== null && (int) Auth::id() === (int) $ticket->user_id2) {
            $pulse = $pulse->withOwnerLabel('You own this');
        }

        return $pulse;
    }

    public function invalidatePulse(int $ticketId): void
    {
        Cache::forget("ticket_pulse:{$ticketId}");
    }

    protected function computePulse(Ticket $ticket): TicketPulse
    {
        $notes = $ticket->relationLoaded('notes')
            ? $ticket->notes
            : $ticket->notes()->with(['user', 'supersedes', 'replies', 'decider'])->get();

        $activeBlocker = $notes
            ->where('notetype', 'blocker')
            ->where('resolved', false)
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->sortByDesc('id')
            ->first();

        $latestAction = $notes
            ->where('notetype', 'action')
            ->where('resolved', false)
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->sortByDesc('id')
            ->first();

        // Ids that some other note supersedes, as an O(1) lookup so the
        // decision filter below is linear instead of scanning all notes per note.
        $supersededIds = $notes->pluck('supersedes_id')->filter()->flip();

        $latestDecision = $notes
            ->where('notetype', 'decision')
            ->where('hide', false)
            ->reject(fn (Note $note) => $supersededIds->has($note->id))
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->sortByDesc('id')
            ->first();

        $openThreads = $notes
            ->whereNull('parent_id')
            ->where('resolved', false)
            ->filter(fn (Note $note) => $note->replies->isNotEmpty() || in_array($note->notetype, ['blocker', 'action', 'decision_needed'], true))
            ->sortBy('id')
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'notetype' => $note->notetype,
                'subject' => Str::of(strip_tags($note->body))->before("\n")->trim()->toString(),
                'reply_count' => $note->replies->count(),
            ])
            ->values()
            ->all();

        $decisionRequests = $notes
            ->whereNull('parent_id')
            ->where('notetype', 'decision_needed')
            ->where('resolved', false)
            ->where('hide', false)
            ->sortBy('id')
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'question' => Str::of(strip_tags($note->body))->before("\n")->trim()->toString(),
                'requester' => $note->user?->name,
                'decider' => $note->decider?->name,
                'decider_id' => $note->decider_id,
                'created_at' => $note->created_at?->toISOString(),
            ])
            ->values()
            ->all();

        $lastActivity = $notes
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->first()?->created_at;

        $isClosed = $ticket->closed_at !== null || Status::isClosed($ticket->status_id);
        $isStale = $lastActivity !== null && $lastActivity->lt(now()->subHours(48));

        return new TicketPulse(
            id: $ticket->id,
            status: $ticket->status->name ?? 'unknown',
            is_blocked: $activeBlocker !== null,
            blocker_reason: $activeBlocker ? strip_tags($activeBlocker->body) : null,
            owner_id: $ticket->user_id2,
            owner_label: $this->deriveOwnerLabel($ticket, $activeBlocker),
            next_action: $this->buildNextAction($latestAction),
            latest_decision: $this->buildLatestDecision($latestDecision, $latestDecision ? $notes->firstWhere('id', $latestDecision->parent_id) : null),
            open_threads: $openThreads,
            decision_requests: $decisionRequests,
            latest_blocker: $this->buildLatestBlocker($activeBlocker),
            execution_state: $this->deriveExecutionState($activeBlocker, $latestAction, $lastActivity, $isClosed),
            last_activity_at: $lastActivity?->toISOString(),
            is_stale: $isStale,
            staleness_message: $isStale ? 'No updates in '.$lastActivity->diffForHumans(now(), [
                'parts' => 2,
                'short' => false,
                'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ]) : null,
        );
    }

    protected function deriveExecutionState(?Note $activeBlocker, ?Note $latestAction, mixed $lastActivity, bool $isClosed): string
    {
        if ($activeBlocker) {
            return 'BLOCKED';
        }

        if (! $lastActivity && ! $latestAction && ! $isClosed) {
            return 'IDLE';
        }

        if (! $latestAction || ($lastActivity !== null && $lastActivity->lt(now()->subHours(48)))) {
            return 'AT RISK';
        }

        return 'ON TRACK';
    }

    protected function deriveOwnerLabel(Ticket $ticket, ?Note $activeBlocker): string
    {
        if ($activeBlocker) {
            $mention = $this->extractMention($activeBlocker);
            if ($mention) {
                return "Waiting on: {$mention}";
            }
        }

        $assigneeName = $ticket->assignee->name ?? 'Unassigned';

        return $assigneeName === 'Unassigned' ? 'Unassigned' : "Owner: {$assigneeName}";
    }

    protected function buildNextAction(?Note $latestAction): array
    {
        if (! $latestAction) {
            return [
                'id' => null,
                'body' => 'No next action defined',
                'assignee' => null,
                'created_at' => null,
            ];
        }

        return [
            'id' => $latestAction->id,
            'body' => $latestAction->body,
            'assignee' => $latestAction->loadMissing('assignee')->assignee
                ? '@'.$latestAction->assignee->name
                : $this->extractMention($latestAction),
            'created_at' => $latestAction->created_at?->toISOString(),
        ];
    }

    protected function buildLatestDecision(?Note $latestDecision, ?Note $thread): ?array
    {
        if (! $latestDecision) {
            return null;
        }

        return [
            'id' => $latestDecision->id,
            'question' => $thread ? Str::of(strip_tags($thread->body))->trim()->before("\n")->trim()->toString() : null,
            'body' => $latestDecision->body,
            'author' => $latestDecision->user?->name,
            'created_at' => $latestDecision->created_at?->toISOString(),
            'supersedes' => $latestDecision->supersedes?->body,
        ];
    }

    protected function buildLatestBlocker(?Note $activeBlocker): ?array
    {
        if (! $activeBlocker) {
            return null;
        }

        return [
            'id' => $activeBlocker->id,
            'body' => $activeBlocker->body,
            'author' => $activeBlocker->user?->name,
            'created_at' => $activeBlocker->created_at?->toISOString(),
        ];
    }

    protected function extractMention(Note $note): ?string
    {
        $user = app(MentionService::class)
            ->resolveUsers($note->body_markdown ?: strip_tags($note->body))
            ->first();

        return $user ? '@'.$user->name : null;
    }
}
