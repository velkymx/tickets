<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Services\KbArticleService;
use App\Services\KbSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * REST parity for the KB MCP tools (search, get, create, update, lookups).
 * Authenticates via the api.token middleware like the rest of /api/v1.
 */
class KbController extends Controller
{
    public function lookups()
    {
        return response()->json([
            'data' => [
                'categories' => KbCategory::orderBy('sort_order')->get(['id', 'name']),
                'tags' => KbTag::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function index(Request $request, KbSearchService $search)
    {
        $user = $request->attributes->get('api_user');

        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:kb_categories,id',
            'status' => 'nullable|in:draft,verified,deprecated',
            'tag_id' => 'nullable|integer|exists:kb_tags,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $search->search(
            $validated['query'] ?? '',
            $user,
            array_filter([
                'category_id' => $validated['category_id'] ?? null,
                'status' => $validated['status'] ?? null,
                'tag_id' => $validated['tag_id'] ?? null,
                'per_page' => $validated['per_page'] ?? null,
            ], fn ($v) => $v !== null),
        );

        return response()->json([
            'data' => $results->getCollection()->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'status' => $a->status,
                'visibility' => $a->visibility,
                'category' => $a->category?->name,
                'tags' => $a->tags->pluck('name'),
                'owner' => $a->owner?->name,
                'updated_at' => $a->updated_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    public function show(Request $request, string $idOrSlug)
    {
        $user = $request->attributes->get('api_user');

        $article = $this->findVisibleArticle($user, $idOrSlug);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $article->load(['category', 'owner', 'tags']);

        return response()->json(['data' => [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'body_markdown' => $article->body_markdown,
            'status' => $article->status,
            'visibility' => $article->visibility,
            'category' => $article->category?->name,
            'tags' => $article->tags->pluck('name'),
            'owner' => $article->owner?->name,
            'version_count' => $article->versions()->count(),
            'reviewed_at' => $article->reviewed_at?->toIso8601String(),
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $article->updated_at?->toIso8601String(),
        ]]);
    }

    public function store(Request $request, KbArticleService $service)
    {
        $user = $request->attributes->get('api_user');

        if (! Gate::forUser($user)->allows('create', KbArticle::class)) {
            return response()->json(['message' => 'You cannot create articles'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body_markdown' => 'required|string|max:100000',
            'category_id' => 'required|integer|exists:kb_categories,id',
            'visibility' => 'required|in:public,internal,restricted',
            'commit_message' => 'required|string|max:255',
            'tags' => 'required|array|min:1',
            'tags.*' => 'integer|exists:kb_tags,id',
            'status' => 'nullable|in:draft,verified,deprecated',
        ]);

        $article = $service->create($validated, $user);

        return response()->json([
            'message' => 'Article created successfully',
            'article' => ['id' => $article->id, 'slug' => $article->slug, 'title' => $article->title],
        ], 201);
    }

    public function update(Request $request, $id, KbArticleService $service)
    {
        $user = $request->attributes->get('api_user');

        $article = KbArticle::find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        if (! Gate::forUser($user)->allows('update', $article)) {
            return response()->json(['message' => 'You cannot edit this article'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body_markdown' => 'required|string|max:100000',
            'category_id' => 'required|integer|exists:kb_categories,id',
            'visibility' => 'required|in:public,internal,restricted',
            'commit_message' => 'required|string|max:255',
            'tags' => 'required|array|min:1',
            'tags.*' => 'integer|exists:kb_tags,id',
            'status' => 'nullable|in:draft,verified,deprecated',
        ]);

        $article = $service->update($article, $validated, $user, $validated['commit_message']);

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => ['id' => $article->id, 'slug' => $article->slug, 'title' => $article->title],
        ]);
    }

    /**
     * Resolve a KB article by numeric id or slug, only if the user may view
     * it. Null when missing or not visible, so callers return a clean 404
     * either way (no leaking of existence).
     */
    private function findVisibleArticle($user, int|string $idOrSlug): ?KbArticle
    {
        $article = is_numeric($idOrSlug)
            ? KbArticle::find((int) $idOrSlug)
            : KbArticle::where('slug', $idOrSlug)->first();

        if (! $article || ! $article->isVisibleTo($user)) {
            return null;
        }

        return $article;
    }
}
