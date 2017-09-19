<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    //

    protected $fillable = [
        'subject', 'description', 'type_id', 'user_id', 'status_id', 'importance_id', 'milestone_id', 'project_id', 'user_id2', 'due_at', 'closed_at','estimate'
    ];

    public function type()
    {
        return $this->belongsTo('App\Type');
    }

    public function milestone()
    {
        return $this->belongsTo('App\Milestone');
    }

    public function project()
    {
        return $this->belongsTo('App\Project');
    }

    public function status()
    {
        return $this->belongsTo('App\Status');
    }

    public function importance()
    {
        return $this->belongsTo('App\Importance');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function assignee()
    {
        return $this->belongsTo('App\User', 'user_id2');
    }

    public function notes()
    {
        return $this->hasMany('App\Note');
    }
}
