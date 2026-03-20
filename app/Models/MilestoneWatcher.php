<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneWatcher extends Model
{
    protected $table = 'milestone_user_watchers';

    protected $fillable = [
        'milestone_id', 'user_id',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
