<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $fillable = [
        'title', 'body', 'started_at', 'completed_at', 'user_id',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(ReleaseTicket::class, 'release_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
