<?php

namespace App\Services;

use App\Contracts\SearchableRepository;
use App\Models\KbArticle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KbSearchService implements SearchableRepository
{
    public function search(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator
    {
        $builder = KbArticle::query()
            ->with(['category', 'tags', 'owner']);

        // Search by keyword (LIKE fallback - works on SQLite and MySQL)
        if ($query !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $query);

            $builder->where(function ($q) use ($escaped) {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('body_markdown', 'like', "%{$escaped}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($escaped) {
                        $tagQuery->where('name', 'like', "%{$escaped}%");
                    });
            });
        }

        // Apply visibility scoping
        $builder->visibleTo($user);

        // Apply filters
        if (! empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (! empty($filters['tag_id'])) {
            $builder->whereHas('tags', fn ($q) => $q->where('kb_tags.id', $filters['tag_id']));
        }

        return $builder->orderByDesc('updated_at')->paginate($filters['per_page'] ?? 15);
    }
}
