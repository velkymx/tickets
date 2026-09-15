<?php

namespace App\Models;

use App\Notifications\WatcherNotification;
use App\Services\NotificationBatchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /** Request keys accepted by the filter scope (also used to repopulate the filter UI). */
    public const FILTER_KEYS = ['milestone_id', 'project_id', 'status_id', 'type_id', 'user_id', 'importance_id', 'q', 'assignee'];

    /** Importance level id for "blocker" (seeded by DefaultsSeeder). */
    public const IMPORTANCE_BLOCKER = 5;

    /**
     * Open tickets flagged as blockers (importance = blocker, not closed).
     * Surfaced in the blocker boxes on project/milestone/home pages.
     */
    public function scopeBlockers(Builder $query): Builder
    {
        return $query
            ->where('importance_id', self::IMPORTANCE_BLOCKER)
            ->whereNotIn('status_id', Status::closedStatusIds());
    }

    /**
     * Apply the shared ticket-list filters from a request-parameter array.
     * Used by every paginated ticket list (/tickets, projects, milestones) so
     * the filtering rules live in one place.
     */
    public function scopeFilter(Builder $query, array $params): Builder
    {
        foreach (['milestone_id', 'project_id', 'status_id', 'type_id', 'user_id', 'user_id2', 'importance_id'] as $field) {
            if (isset($params[$field]) && is_numeric($params[$field])) {
                $query->where($field, $params[$field]);
            }
        }

        if (($params['status_id'] ?? null) === 'none') {
            $query->whereNotIn('status_id', Status::closedStatusIds());
        }

        if (($params['status_id'] ?? null) === 'closed') {
            $query->whereIn('status_id', Status::closedStatusIds());
        }

        if (! empty($params['q'])) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $params['q']);
            $query->where('subject', 'like', '%'.$search.'%');
        }

        if (($params['assignee'] ?? null) === 'me' && auth()->check()) {
            $query->where('user_id2', auth()->id());
        }

        return $query;
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($ticket) {
            if (! $ticket->isDirty('status_id')) {
                return;
            }

            $wasClosed = Status::isClosed((int) $ticket->getOriginal('status_id'));
            $isClosed = Status::isClosed((int) $ticket->status_id);

            // Auto-manage closed_at on status transitions, unless explicitly set.
            if ($isClosed && ! $wasClosed && ! $ticket->isDirty('closed_at')) {
                $ticket->closed_at = now();
            }

            if ($wasClosed && ! $isClosed && ! $ticket->isDirty('closed_at')) {
                $ticket->closed_at = null;
            }
        });

        static::updated(function ($ticket) {
            if ($ticket->wasChanged('status_id')) {
                $wasClosed = Status::isClosed((int) $ticket->getOriginal('status_id'));
                $isClosed = Status::isClosed((int) $ticket->status_id);

                if ($wasClosed && ! $isClosed) {
                    $ticket->recordReopenAuditNote();
                }

                app(\App\Services\TicketPulseService::class)->invalidatePulse($ticket->id);
            }

            if ($ticket->wasChanged(['subject', 'description', 'status_id', 'user_id2', 'milestone_id', 'project_id', 'importance_id', 'due_at', 'closed_at', 'estimate', 'storypoints', 'actual'])) {
                $ticket->notifyWatchers('Ticket', auth()->id() ?? 0);
            }
        });
    }

    /**
     * Leave a small changelog audit note when a closed ticket is reopened.
     */
    public function recordReopenAuditNote(): void
    {
        $userId = auth()->id() ?? $this->user_id2 ?? $this->user_id;

        if (! $userId) {
            return;
        }

        $oldStatus = Status::find($this->getOriginal('status_id'));
        $message = 'Ticket reopened (status changed from '
            .($oldStatus->name ?? 'closed')
            .' to '.($this->status->name ?? 'open').').';

        Note::create([
            'user_id' => $userId,
            'ticket_id' => $this->id,
            'body' => $message,
            'body_markdown' => $message,
            'notetype' => 'changelog',
        ]);
    }

    private function notifyWatchers(string $type, ?int $exceptUserId = null): void
    {
        $url = url("/tickets/{$this->id}");
        $message = "The {$type} '{$this->subject}' has been updated.";

        $this->load('watchers.user');

        $this->watchers->each(function ($watcher) use ($type, $message, $url, $exceptUserId) {
            if ($watcher->user_id !== $exceptUserId && ! $watcher->muted && $watcher->user?->email) {
                app(NotificationBatchService::class)->dispatch(
                    $watcher->user,
                    new WatcherNotification($type, $message, $url),
                    $this->id
                );
            }
        });
    }

    protected $fillable = [
        'subject', 'description', 'type_id', 'status_id', 'importance_id', 'milestone_id', 'project_id',
        'due_at', 'closed_at', 'estimate', 'storypoints', 'actual', 'user_id', 'user_id2',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'closed_at' => 'datetime',
            'estimate' => 'decimal:2',
            'actual' => 'integer',
            'storypoints' => 'integer',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function getActualHoursAttribute()
    {
        return (int) ($this->attributes['notes_sum_hours'] ?? 0);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function importance(): BelongsTo
    {
        return $this->belongsTo(Importance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id2');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(TicketEstimate::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TicketUserWatcher::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(TicketView::class);
    }
}
