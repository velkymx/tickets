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
        'pinned', 'resolved', 'resolved_by', 'supersedes_id', 'resolution_message',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'hide' => 'boolean',
            'edited_at' => 'datetime',
            'pinned' => 'boolean',
            'resolved' => 'boolean',
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
}
