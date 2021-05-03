<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketEstimate extends Model
{

    protected $fillable = [
        'user_id', 'ticket_id','storypoints'
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
