<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get a knowledge base article by slug or numeric id, including body, category, tags, and status. Only returns articles you are allowed to see.')]
#[IsReadOnly]
#[IsIdempotent]
class GetKbArticleTool extends KbTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'slug' => 'required|string|max:255',
        ]);

        $article = $this->findVisibleArticle($user, $validated['slug']);

        if (! $article) {
            return $this->articleNotFound();
        }

        $article->load(['category', 'owner', 'tags']);

        return Response::json([
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
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->description('The article slug, or its numeric id.')->required(),
        ];
    }
}
