<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KbArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'body_markdown', 'body_html',
        'category_id', 'user_id', 'owner_id',
        'status', 'visibility', 'reviewed_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KbCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KbTag::class, 'kb_article_tag', 'article_id', 'tag_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(KbArticleVersion::class, 'article_id')
            ->orderByDesc('version_number');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(KbArticlePermission::class, 'article_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KbArticleAttachment::class, 'article_id');
    }

    public function isVisibleTo(?User $user): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->visibleTo($user)
            ->exists();
    }

    /**
     * Limit a query to articles the given user may see. Mirrors the policy so
     * lists and related-article panels cannot leak titles.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->isKbAdmin()) {
            return $query;
        }

        $public = fn (Builder $query) => $query
            ->where('visibility', 'public')
            ->where('status', 'verified');

        if ($user === null) {
            return $query->where($public);
        }

        return $query->where(function (Builder $query) use ($user, $public) {
            $query
                ->where($public)
                ->orWhere(fn (Builder $query) => $query
                    ->where('status', 'draft')
                    ->where('user_id', $user->id))
                ->orWhere(fn (Builder $query) => $query
                    ->where('visibility', 'internal')
                    ->where('status', '!=', 'draft'))
                ->orWhere(fn (Builder $query) => $query
                    ->where('visibility', 'restricted')
                    ->where('status', '!=', 'draft')
                    ->where(fn (Builder $query) => $query
                        ->where('owner_id', $user->id)
                        ->orWhereHas('permissions', fn (Builder $query) => $query->where('user_id', $user->id))));
        });
    }
}
