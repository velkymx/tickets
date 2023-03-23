<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    //

    protected $fillable = [
        'subject', 'description', 'type_id', 'user_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2', 'due_at', 'closed_at','estimate','storypoints'
    ];

    public function type()
    {
        return $this->belongsTo('App\Models\Type');
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
