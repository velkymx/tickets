<?php

namespace App\Mcp\Tools;

use App\Services\KbSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search the knowledge base by keyword (title, body, tags). Results are scoped to what you are allowed to see. Optionally filter by category, status, or tag.')]
#[IsReadOnly]
#[IsIdempotent]
class KbSearchTool extends KbTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:kb_categories,id',
            'status' => 'nullable|in:draft,verified,deprecated',
            'tag_id' => 'nullable|integer|exists:kb_tags,id',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = app(KbSearchService::class)->search(
            $validated['query'] ?? '',
            $user,
            array_filter([
                'category_id' => $validated['category_id'] ?? null,
                'status' => $validated['status'] ?? null,
                'tag_id' => $validated['tag_id'] ?? null,
                'per_page' => $validated['per_page'] ?? null,
            ], fn ($v) => $v !== null),
        );

        return Response::json([
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

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Keyword to search titles, bodies, and tags.'),
            'category_id' => $schema->integer()->description('Filter by category ID (see kb lookups).'),
            'status' => $schema->string()->description('Filter by status: draft, verified, or deprecated.'),
            'tag_id' => $schema->integer()->description('Filter by tag ID (see kb lookups).'),
            'per_page' => $schema->integer()->description('Items per page, max 100. Default 20.'),
        ];
    }
}
