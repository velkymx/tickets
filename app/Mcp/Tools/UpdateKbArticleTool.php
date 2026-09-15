<?php

namespace App\Mcp\Tools;

use App\Models\KbArticle;
use App\Services\KbArticleService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Update a knowledge base article you own (or have edit permission on). Creates a new version. Resolve category and tag IDs with the kb lookups tool first.')]
class UpdateKbArticleTool extends KbTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'id' => 'required|integer|exists:kb_articles,id',
            'title' => 'required|string|max:255',
            'body_markdown' => 'required|string|max:100000',
            'category_id' => 'required|integer|exists:kb_categories,id',
            'visibility' => 'required|in:public,internal,restricted',
            'commit_message' => 'required|string|max:255',
            'tags' => 'required|array|min:1',
            'tags.*' => 'integer|exists:kb_tags,id',
            'status' => 'nullable|in:draft,verified,deprecated',
        ]);

        $article = KbArticle::find($validated['id']);

        if (! $article) {
            return $this->articleNotFound();
        }

        if (! Gate::forUser($user)->allows('update', $article)) {
            return Response::error('You cannot edit this article.');
        }

        $article = app(KbArticleService::class)->update($article, $validated, $user, $validated['commit_message']);

        return Response::json([
            'message' => 'Article updated.',
            'article' => ['id' => $article->id, 'slug' => $article->slug, 'title' => $article->title],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The article id to update.')->required(),
            'title' => $schema->string()->description('Article title.')->required(),
            'body_markdown' => $schema->string()->description('Article body in Markdown.')->required(),
            'category_id' => $schema->integer()->description('Category ID (see kb lookups).')->required(),
            'visibility' => $schema->string()->description('public, internal, or restricted.')->required(),
            'commit_message' => $schema->string()->description('Version note describing this edit.')->required(),
            'tags' => $schema->array()->description('Tag IDs (at least one, see kb lookups).')->required(),
            'status' => $schema->string()->description('draft, verified, or deprecated.'),
        ];
    }
}
