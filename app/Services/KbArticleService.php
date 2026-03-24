<?php

namespace App\Services;

use App\Models\KbArticle;
use App\Models\KbArticleVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KbArticleService
{
    public function __construct(private MarkdownService $markdownService) {}

    public function create(array $data, User $author): KbArticle
    {
        $html = $this->markdownService->parse($data['body_markdown']);
        $slug = $this->generateUniqueSlug($data['title']);

        $article = KbArticle::create([
            'title' => $data['title'],
            'slug' => $slug,
            'body_markdown' => $data['body_markdown'],
            'body_html' => $html,
            'category_id' => $data['category_id'],
            'user_id' => $author->id,
            'owner_id' => $author->id,
            'status' => $data['status'] ?? 'draft',
            'visibility' => $data['visibility'] ?? 'internal',
        ]);

        if (! empty($data['tags'])) {
            $article->tags()->sync($data['tags']);
        }

        $this->createVersion($article, $author, $data['commit_message']);

        return $article->load(['tags', 'versions', 'category']);
    }

    public function update(KbArticle $article, array $data, User $editor, string $commitMessage): KbArticle
    {
        $html = $this->markdownService->parse($data['body_markdown']);

        $article->update([
            'title' => $data['title'],
            'body_markdown' => $data['body_markdown'],
            'body_html' => $html,
            'category_id' => $data['category_id'],
            'visibility' => $data['visibility'] ?? $article->visibility,
            'status' => $data['status'] ?? $article->status,
        ]);

        if (array_key_exists('tags', $data)) {
            $article->tags()->sync($data['tags'] ?? []);
        }

        $this->createVersion($article, $editor, $commitMessage);

        return $article->fresh()->load(['tags', 'versions', 'category']);
    }

    public function createVersion(KbArticle $article, User $editor, string $commitMessage): KbArticleVersion
    {
        return DB::transaction(function () use ($article, $editor, $commitMessage) {
            $nextVersion = KbArticleVersion::where('article_id', $article->id)
                ->lockForUpdate()
                ->max('version_number') + 1;

            return KbArticleVersion::create([
                'article_id' => $article->id,
                'user_id' => $editor->id,
                'title' => $article->title,
                'body_markdown' => $article->body_markdown,
                'body_html' => $article->body_html,
                'commit_message' => $commitMessage,
                'version_number' => $nextVersion,
            ]);
        });
    }

    public function transferOwnership(KbArticle $article, User $newOwner): void
    {
        $article->update(['owner_id' => $newOwner->id]);
    }

    public function markReviewed(KbArticle $article): void
    {
        $article->update(['reviewed_at' => now()]);
    }

    public function changeStatus(KbArticle $article, string $status): void
    {
        $updates = ['status' => $status];

        if ($status === 'verified' && ! $article->published_at) {
            $updates['published_at'] = now();
        }

        $article->update($updates);
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while (KbArticle::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
