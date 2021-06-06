<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function tickets()
    {
        return $this->hasMany('App\Tickets');
    }
}
