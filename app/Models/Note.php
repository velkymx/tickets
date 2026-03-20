<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    protected $fillable = [
        'body', 'user_id', 'ticket_id', 'hours', 'notetype', 'hide',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'hide' => 'boolean',
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
