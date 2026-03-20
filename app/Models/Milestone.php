<?php

namespace App\Models;

use App\Notifications\WatcherNotification;
use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'scrummaster_user_id', 'owner_user_id', 'start_at', 'due_at', 'end_at',
    ];

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

        if ($this->relationLoaded('watchers')) {
            $notifyUsers = $notifyUsers->merge($this->watchers->pluck('user'))->filter();
        }

        $notifyUsers->each(function ($user) use ($type, $message, $url, $exceptUserId) {
            if ($user->id !== $exceptUserId && $user->email) {
                $user->notify(new WatcherNotification($type, $message, $url));
            }
        });
    }

    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket');
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'owner_user_id');
    }

    public function scrummaster()
    {
        return $this->belongsTo('App\Models\User', 'scrummaster_user_id');
    }

    public function watchers()
    {
        return $this->hasMany(MilestoneWatcher::class);
    }
}
