<?php

namespace Tests\Feature\Controllers;

use App\Models\KbArticle;
use App\Models\KbArticleVersion;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbVersionControllerTest extends TestCase
{
    use SeedsDatabase;

    private function createArticleWithVersions(User $user): KbArticle
    {
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->internal()->create([
            'user_id' => $user->id,
            'owner_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $article->tags()->attach($tag);

        KbArticleVersion::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'title' => 'Version 1',
            'body_markdown' => 'First content',
            'body_html' => '<p>First content</p>',
            'commit_message' => 'Initial',
            'version_number' => 1,
        ]);

        KbArticleVersion::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'title' => 'Version 2',
            'body_markdown' => 'Updated content',
            'body_html' => '<p>Updated content</p>',
            'commit_message' => 'Updated section',
            'version_number' => 2,
        ]);

        return $article;
    }

    #[Test]
    public function it_shows_version_history(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $article = $this->createArticleWithVersions($user);

        $this->actingAs($user)->get("/kb/{$article->slug}/history")
            ->assertOk()
            ->assertSeeInOrder(['v2', 'Updated section', 'v1', 'Initial']);
    }

    #[Test]
    public function it_shows_a_specific_version(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $article = $this->createArticleWithVersions($user);

        $this->actingAs($user)->get("/kb/{$article->slug}/history/1")
            ->assertOk()
            ->assertSee('Version 1: Version 1')
            ->assertSee('First content')
            ->assertDontSee('Updated content');
    }

    #[Test]
    public function it_shows_diff_between_versions(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $article = $this->createArticleWithVersions($user);

        $this->actingAs($user)->get("/kb/{$article->slug}/diff/1/2")
            ->assertOk()
            ->assertSee('First content')
            ->assertSee('Updated content');
    }

    #[Test]
    public function it_restores_a_version(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $article = $this->createArticleWithVersions($user);

        $this->actingAs($user)->post("/kb/{$article->slug}/restore/1")
            ->assertRedirect("/kb/{$article->slug}");

        $article->refresh();
        $this->assertSame('Version 1', $article->title);
        $this->assertSame('First content', $article->body_markdown);
        $this->assertDatabaseHas('kb_article_versions', ['article_id' => $article->id, 'version_number' => 3]);
    }

    #[Test]
    public function it_returns_404_for_missing_version(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $article = $this->createArticleWithVersions($user);

        $this->actingAs($user)->get("/kb/{$article->slug}/history/99")->assertNotFound();
    }

    #[Test]
    public function unauthorized_user_cannot_view_restricted_article_history(): void
    {
        $owner = User::factory()->create(['kb_role' => 'author']);
        $stranger = User::factory()->create();
        $article = $this->createArticleWithVersions($owner);
        $article->update(['visibility' => 'restricted']);

        $this->actingAs($stranger)->get("/kb/{$article->slug}/history")->assertForbidden();
    }
}
