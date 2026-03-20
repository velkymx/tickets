<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'title', 'body', 'started_at', 'completed_at', 'user_id',
    ];

    public function tickets()
    {
        return $this->hasMany('App\Models\ReleaseTicket', 'release_id');
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
