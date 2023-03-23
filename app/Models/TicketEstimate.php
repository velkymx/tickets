<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEstimate extends Model
{

    protected $fillable = [
        'user_id', 'ticket_id','storypoints'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
  
    public function ticket()
    {
        return $this->belongsTo('App\Models\Ticket');
    }    
}
