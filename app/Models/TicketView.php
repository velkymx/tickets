<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketView extends Model
{
    protected $table = 'ticket_views';

    protected $fillable = [
        'user_id', 'ticket_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket');
    }
}
