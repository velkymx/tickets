<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEstimate extends Model
{
    protected $table = 'ticket_estimates';

    protected $fillable = [
        'user_id', 'ticket_id', 'storypoints',
    ];

    protected $casts = [
        'storypoints' => 'integer',
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
