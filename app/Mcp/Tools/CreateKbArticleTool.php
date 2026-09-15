<?php

namespace App\Mcp\Tools;

use App\Services\KbArticleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a knowledge base article. Resolve category and tag IDs with the kb lookups tool first. You become the article owner.')]
class CreateKbArticleTool extends KbTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        if (! Gate::forUser($user)->allows('create', \App\Models\KbArticle::class)) {
            return Response::error('You cannot create articles.');
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

        $article = app(KbArticleService::class)->create($validated, $user);

        return Response::json([
            'message' => 'Article created.',
            'article' => ['id' => $article->id, 'slug' => $article->slug, 'title' => $article->title],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Article title.')->required(),
            'body_markdown' => $schema->string()->description('Article body in Markdown.')->required(),
            'category_id' => $schema->integer()->description('Category ID (see kb lookups).')->required(),
            'visibility' => $schema->string()->description('public, internal, or restricted.')->required(),
            'commit_message' => $schema->string()->description('Version note for this creation.')->required(),
            'tags' => $schema->array()->description('Tag IDs (at least one, see kb lookups).')->required(),
            'status' => $schema->string()->description('draft (default), verified, or deprecated.'),
        ];
    }
}
