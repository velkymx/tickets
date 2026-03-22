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
        return Cache::remember("ticket_pulse:{$ticket->id}", now()->addHours(24), function () use ($ticket) {
            return $this->computePulse($ticket);
        });
    }

    public function invalidatePulse(int $ticketId): void
    {
        Cache::forget("ticket_pulse:{$ticketId}");
    }

    protected function computePulse(Ticket $ticket): TicketPulse
    {
        $notes = $ticket->notes()->with(['user', 'supersedes', 'replies'])->get();

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

        $latestDecision = $notes
            ->where('notetype', 'decision')
            ->where('hide', false)
            ->filter(fn (Note $note) => ! $notes->contains(fn (Note $candidate) => $candidate->supersedes_id === $note->id))
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->sortByDesc('id')
            ->first();

        $openThreads = $notes
            ->whereNull('parent_id')
            ->where('resolved', false)
            ->filter(fn (Note $note) => $note->replies->isNotEmpty())
            ->map(fn (Note $note) => [
                'id' => $note->id,
                'subject' => Str::of(strip_tags($note->body))->before("\n")->trim()->toString(),
                'reply_count' => $note->replies->count(),
            ])
            ->values()
            ->all();

        $lastActivity = $notes
            ->sortByDesc(fn (Note $note) => $note->created_at?->timestamp ?? 0)
            ->first()?->created_at;

        $isClosed = $ticket->closed_at !== null || Status::isClosed($ticket->status_id);
        $isStale = $lastActivity !== null && $lastActivity->lt(now()->subHours(48));
        $status = $activeBlocker ? 'BLOCKED' : $ticket->status->name;

        return new TicketPulse(
            id: $ticket->id,
            status: $status,
            is_blocked: $activeBlocker !== null,
            blocker_reason: $activeBlocker?->body,
            owner_id: $ticket->user_id2,
            owner_label: $this->deriveOwnerLabel($ticket, $activeBlocker),
            next_action: $this->buildNextAction($latestAction),
            latest_decision: $this->buildLatestDecision($latestDecision),
            open_threads: $openThreads,
            latest_blocker: $this->buildLatestBlocker($activeBlocker),
            execution_state: $this->deriveExecutionState($activeBlocker, $latestAction, $lastActivity, $isClosed),
            last_activity_at: $lastActivity,
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
            $mention = $this->extractMention($activeBlocker->body);
            if ($mention) {
                return "Waiting on: {$mention}";
            }
        }

        if (Auth::id() !== null && (int) Auth::id() === (int) $ticket->user_id2) {
            return 'You own this';
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
            'assignee' => $this->extractMention($latestAction->body),
            'created_at' => $latestAction->created_at,
        ];
    }

    protected function buildLatestDecision(?Note $latestDecision): ?array
    {
        if (! $latestDecision) {
            return null;
        }

        return [
            'id' => $latestDecision->id,
            'body' => $latestDecision->body,
            'author' => $latestDecision->user?->name,
            'created_at' => $latestDecision->created_at,
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
            'created_at' => $activeBlocker->created_at,
        ];
    }

    protected function extractMention(string $body): ?string
    {
        if (preg_match('/@([\w.\-]+)/', $body, $matches) !== 1) {
            return null;
        }

        return '@'.$matches[1];
    }
}
