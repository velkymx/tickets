<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NoteAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id', 'user_id', 'ticket_id', 'filename', 'path', 'mime_type', 'size',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::url($this->path),
        );
    }

    public function isImage(): Attribute
    {
        return Attribute::make(
            get: fn () => str_starts_with($this->mime_type, 'image/'),
        );
    }
}
