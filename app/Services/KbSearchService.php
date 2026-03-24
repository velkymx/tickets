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

        // Search by keyword (LIKE fallback — works on SQLite and MySQL)
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('body_markdown', 'like', "%{$query}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($query) {
                        $tagQuery->where('name', 'like', "%{$query}%");
                    });
            });
        }

        // Apply visibility scoping
        $this->applyVisibilityScope($builder, $user);

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

    private function applyVisibilityScope($builder, ?User $user): void
    {
        if (! $user) {
            $builder->where('visibility', 'public')->where('status', 'verified');

            return;
        }

        if ($user->isKbAdmin()) {
            return; // Admins see everything
        }

        $builder->where(function ($q) use ($user) {
            // Own drafts
            $q->where(function ($draft) use ($user) {
                $draft->where('status', 'draft')->where('user_id', $user->id);
            })
            // Public verified
            ->orWhere(function ($pub) {
                $pub->where('visibility', 'public')->where('status', '!=', 'draft');
            })
            // Internal non-draft
            ->orWhere(function ($internal) {
                $internal->where('visibility', 'internal')->where('status', '!=', 'draft');
            })
            // Restricted where user is owner or permitted
            ->orWhere(function ($restricted) use ($user) {
                $restricted->where('visibility', 'restricted')
                    ->where('status', '!=', 'draft')
                    ->where(function ($access) use ($user) {
                        $access->where('owner_id', $user->id)
                            ->orWhereHas('permissions', fn ($p) => $p->where('user_id', $user->id));
                    });
            });
        });
    }
}
