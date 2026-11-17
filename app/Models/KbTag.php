<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

class KbTag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public static function slugIsTaken(string $slug, ?int $ignoreId = null): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            if ($tag->slug !== '' && self::slugIsTaken($tag->slug, $tag->exists ? $tag->id : null)) {
                throw ValidationException::withMessages(['name' => 'A tag with a similar name already exists.']);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KbArticle::class, 'kb_article_tag', 'tag_id', 'article_id');
    }
}
