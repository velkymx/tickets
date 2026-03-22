<?php

namespace App\Models;

use App\Notifications\WatcherNotification;
use App\Services\NotificationBatchService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($ticket) {
            $ticket->notifyWatchers('Ticket');
        });
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
        'subject', 'description', 'type_id', 'user_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2', 'due_at', 'closed_at', 'estimate', 'storypoints', 'actual',
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
        if (isset($this->attributes['notes_sum_hours'])) {
            return (int) $this->attributes['notes_sum_hours'];
        }

        return $this->notes()->sum('hours');
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
