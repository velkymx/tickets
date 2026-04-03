<?php

namespace App\Models;

use App\Notifications\WatcherNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Milestone extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'scrummaster_user_id', 'owner_user_id', 'start_at', 'due_at', 'end_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'due_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($milestone) {
            $milestone->notifyWatchers('Milestone');
        });
    }

    private function notifyWatchers(string $type, ?int $exceptUserId = null): void
    {
        $url = url("/milestone/show/{$this->id}");
        $message = "The {$type} '{$this->name}' has been updated.";

        $notifyUsers = collect([$this->owner, $this->scrummaster])->filter();

        $this->load('watchers.user');
        if ($this->watchers->isNotEmpty()) {
            $notifyUsers = $notifyUsers->merge($this->watchers->pluck('user'))->filter();
        }

        $notifyUsers->each(function ($user) use ($type, $message, $url, $exceptUserId) {
            if ($user->id !== $exceptUserId && $user->email) {
                $user->notify(new WatcherNotification($type, $message, $url));
            }
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function scrummaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scrummaster_user_id');
    }

    public function watchers()
    {
        return $this->hasMany(MilestoneWatcher::class);
    }
}
