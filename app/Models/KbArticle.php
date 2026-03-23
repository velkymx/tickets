<?php

namespace App\Models;

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
        // Public + verified = anyone can see
        if ($this->visibility === 'public' && $this->status === 'verified') {
            return true;
        }

        // No user after this point
        if ($user === null) {
            return false;
        }

        // Admins and KB admins can see everything
        if ($user->isKbAdmin()) {
            return true;
        }

        // Draft only visible to the author
        if ($this->status === 'draft') {
            return $this->user_id === $user->id;
        }

        // Internal = any authenticated user
        if ($this->visibility === 'internal') {
            return true;
        }

        // Restricted = owner or permitted users
        if ($this->visibility === 'restricted') {
            if ($this->owner_id === $user->id) {
                return true;
            }

            return $this->permissions()->where('user_id', $user->id)->exists();
        }

        return false;
    }
}
