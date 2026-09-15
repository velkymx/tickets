<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesApiUser;
use App\Models\KbArticle;
use App\Models\User;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class KbTool extends Tool
{
    use ResolvesApiUser;

    /**
     * Resolve a KB article by numeric id or slug, but only if the user may
     * view it. Null when missing or not visible, so callers return a clean
     * not-found either way (no leaking of existence).
     */
    protected function findVisibleArticle(User $user, int|string $idOrSlug): ?KbArticle
    {
        $article = is_numeric($idOrSlug)
            ? KbArticle::find((int) $idOrSlug)
            : KbArticle::where('slug', $idOrSlug)->first();

        if (! $article || ! $article->isVisibleTo($user)) {
            return null;
        }

        return $article;
    }

    protected function articleNotFound(): Response
    {
        return Response::error('Article not found.');
    }
}
