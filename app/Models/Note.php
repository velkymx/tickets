<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'body', 'user_id', 'ticket_id', 'parent_id', 'hours', 'notetype', 'hide', 'edited_at',
        'pinned', 'resolved', 'resolved_by', 'supersedes_id', 'resolution_message',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'hide' => 'boolean',
            'edited_at' => 'datetime',
            'pinned' => 'boolean',
            'resolved' => 'boolean',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Note::class, 'parent_id');
    }

    public function replies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Note::class, 'parent_id');
    }
}
