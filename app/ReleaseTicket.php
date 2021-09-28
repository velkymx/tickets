<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReleaseTicket extends Model
{
    public function ticket()
    {
        return $this->belongsTo('App\Ticket','ticket_id');
    }
    
    public function release()
    {
        return $this->belongsTo('App\Release');
    }    
}
