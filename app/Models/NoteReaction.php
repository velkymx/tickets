<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id', 'user_id', 'emoji',
    ];

    public const ALLOWED_EMOJIS = ['thumbsup', 'eyes'];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
