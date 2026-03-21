<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEstimate extends Model
{
    protected $fillable = [
        'user_id', 'ticket_id', 'storypoints',
    ];

    protected function casts(): array
    {
        return [
            'storypoints' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
