<?php

namespace App\Policies;

use App\Models\KbArticle;
use App\Models\User;

class KbArticlePolicy
{
    public function before(?User $user, string $ability): ?bool
    {
        if ($user && ($user->isAdmin() || $user->kb_role === 'admin')) {
            return true;
        }

        return null;
    }

    public function view(?User $user, KbArticle $article): bool
    {
        if ($article->visibility === 'public' && $article->status !== 'draft') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($article->status === 'draft') {
            return $article->user_id === $user->id;
        }

        if ($article->visibility === 'internal') {
            return true;
        }

        if ($article->visibility === 'restricted') {
            return $article->owner_id === $user->id
                || $article->permissions()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function create(?User $user): bool
    {
        return $user && ($user->kb_role === 'author' || $user->kb_role === 'admin');
    }

    public function update(?User $user, KbArticle $article): bool
    {
        if (! $user) {
            return false;
        }

        return $article->owner_id === $user->id
            || $article->permissions()->where('user_id', $user->id)->exists();
    }

    public function delete(?User $user, KbArticle $article): bool
    {
        return false; // Only admins, handled by before()
    }

    public function restore(?User $user, KbArticle $article): bool
    {
        return $this->update($user, $article);
    }
}
