<?php

namespace App\Models;

use App\Notifications\WatcherNotification;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
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

        $this->watchers->each(function ($watcher) use ($type, $message, $url, $exceptUserId) {
            if ($watcher->user_id !== $exceptUserId && $watcher->user?->email) {
                $watcher->user->notify(new WatcherNotification($type, $message, $url));
            }
        });
    }

    protected $fillable = [
        'subject', 'description', 'type_id', 'user_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2', 'due_at', 'closed_at', 'estimate', 'storypoints', 'actual',
    ];

    public function type()
    {
        return $this->belongsTo('App\Models\Type');
    }

    public function getActualHoursAttribute()
    {
        return $this->notes()->sum('hours');
    }

    public function milestone()
    {
        return $this->belongsTo('App\Models\Milestone');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\Status');
    }

    public function importance()
    {
        return $this->belongsTo('App\Models\Importance');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function assignee()
    {
        return $this->belongsTo('App\Models\User', 'user_id2');
    }

    public function notes()
    {
        return $this->hasMany('App\Models\Note');
    }

    public function userstorypoints()
    {
        return $this->hasMany('App\Models\TicketEstimate');
    }

    public function watchers()
    {
        return $this->hasMany('App\Models\TicketUserWatcher');
    }

    public function views()
    {
        return $this->hasMany('App\Models\TicketView');
    }
}
