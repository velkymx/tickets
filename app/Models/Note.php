<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'body', 'body_markdown', 'user_id', 'ticket_id', 'parent_id', 'hours', 'notetype', 'hide', 'edited_at',
        'pinned', 'resolved', 'resolved_by', 'supersedes_id', 'resolution_message', 'decider_id', 'assignee_id',
    ];

    public const DECISION_WARNING_HOURS = 24;

    public const DECISION_DANGER_HOURS = 72;

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'hide' => 'boolean',
            'edited_at' => 'datetime',
            'pinned' => 'boolean',
            'resolved' => 'boolean',
            'decider_id' => 'integer',
            'assignee_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Note::class, 'parent_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(NoteReaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoteAttachment::class);
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(Mention::class);
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decider_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'supersedes_id');
    }

    public function supersededBy(): HasOne
    {
        return $this->hasOne(Note::class, 'supersedes_id');
    }

    // Helpers
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function groupedReactions(?int $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        return $this->reactions->groupBy('emoji')->map(function ($group) use ($userId) {
            return [
                'count' => $group->count(),
                'reacted' => $group->contains('user_id', $userId),
            ];
        });
    }

    public function hasReacted(User $user, string $emoji): bool
    {
        return $this->reactions()->where('user_id', $user->id)->where('emoji', $emoji)->exists();
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function isSuperseded(): bool
    {
        return $this->supersededBy()->exists();
    }

    public function isStaleBlocker(?Carbon $referenceTime = null): bool
    {
        if ($this->notetype !== 'blocker' || $this->resolved) {
            return false;
        }

        $referenceTime = $referenceTime ?? now();

        return $this->created_at && $this->created_at->diffInHours($referenceTime) > 48;
    }

    public function decisionUrgency(?Carbon $referenceTime = null): string
    {
        $hours = $this->created_at ? $this->created_at->diffInHours($referenceTime ?? now()) : 0;

        return match (true) {
            $hours >= self::DECISION_DANGER_HOURS => 'danger',
            $hours >= self::DECISION_WARNING_HOURS => 'warning',
            default => 'info',
        };
    }

    // Scopes
    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActiveActions($query)
    {
        return $query->where('notetype', 'action')->where('resolved', false);
    }

    public function scopeActiveBlockers($query)
    {
        return $query->where('notetype', 'blocker')->where('resolved', false);
    }

    public function scopeOpenDecisionRequests($query)
    {
        return $query->where('notetype', 'decision_needed')
            ->where('resolved', false)
            ->where('hide', false)
            ->whereNull('parent_id');
    }

    public function scopeAwaitingDecisionFrom($query, int $userId)
    {
        return $query->openDecisionRequests()
            ->where('decider_id', $userId)
            ->whereHas('ticket', fn ($q) => $q->open());
    }
}
