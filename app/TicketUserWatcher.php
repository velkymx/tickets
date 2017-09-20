<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketUserWatcher extends Model
{
    //

    protected $fillable = [
        'user_id', 'ticket_id'
    ];


    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket');
    }

}
