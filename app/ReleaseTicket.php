<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseTicket extends Model
{


    public function tickets()
    {
        return $this->belongsTo('App\Ticket');
    }
    public function releases()
    {
        return $this->belongsTo('App\Release');
    }
}
