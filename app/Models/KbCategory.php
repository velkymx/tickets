<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class KbCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'parent_id'];

    protected static function booted(): void
    {
        // Backstop for every write path (controller, factory, seeder,
        // tinker): a cycle hides whole subtrees from the KB UI, so fail
        // loud instead of writing one. The controller also validates for
        // friendly form errors; this runs on save regardless of caller.
        static::saving(function (self $category): void {
            $parentId = $category->parent_id;

            if (! $parentId || ! $category->exists) {
                return;
            }

            if ((int) $parentId === $category->id) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot be its own parent.']);
            }

            if (self::find($parentId)?->isDescendantOf($category)) {
                throw ValidationException::withMessages(['parent_id' => 'A category cannot move under its own child.']);
            }
        });
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KbArticle::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function isDescendantOf(self $ancestor): bool
    {
        $parent = $this->parent;

        while ($parent) {
            if ($parent->is($ancestor)) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}
