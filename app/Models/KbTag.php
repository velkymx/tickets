<?php

namespace App\Models;

use App\Models\Concerns\ReportsUniqueSlugCollisions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KbTag extends Model
{
    use HasFactory;
    use ReportsUniqueSlugCollisions;

    protected $fillable = ['name', 'slug'];

    protected function slugCollisionMessage(): string
    {
        return 'A tag with a similar name already exists.';
    }

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            if (blank($tag->slug) && filled($tag->name)) {
                $tag->slug = Str::slug($tag->name);
            }

            if (blank($tag->slug)) {
                throw ValidationException::withMessages([
                    'name' => 'The tag name must contain at least one letter or number.',
                ]);
            }

            if (self::slugIsTaken($tag->slug, $tag->exists ? $tag->id : null)) {
                throw ValidationException::withMessages([
                    'name' => 'A tag with a similar name already exists.',
                ]);
            }
        });
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KbArticle::class, 'kb_article_tag', 'tag_id', 'article_id');
    }
}
