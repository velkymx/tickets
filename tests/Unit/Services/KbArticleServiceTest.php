<?php

namespace Tests\Unit\Services;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use App\Services\KbArticleService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbArticleServiceTest extends TestCase
{
    use SeedsDatabase;

    private KbArticleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(KbArticleService::class);
    }

    #[Test]
    public function it_creates_an_article_with_initial_version(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        $article = $this->service->create([
            'title' => 'Getting Started',
            'body_markdown' => '# Hello World',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [$tag->id],
            'commit_message' => 'Initial creation',
        ], $user);

        $this->assertDatabaseHas('kb_articles', ['title' => 'Getting Started', 'status' => 'draft']);
        $this->assertCount(1, $article->versions);
        $this->assertSame(1, $article->versions->first()->version_number);
        $this->assertSame('Initial creation', $article->versions->first()->commit_message);
        $this->assertTrue($article->tags->contains($tag));
    }

    #[Test]
    public function it_generates_a_unique_slug(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();

        $article1 = $this->service->create([
            'title' => 'My Article',
            'body_markdown' => 'Content',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [],
            'commit_message' => 'First',
        ], $user);

        $article2 = $this->service->create([
            'title' => 'My Article',
            'body_markdown' => 'Different content',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [],
            'commit_message' => 'Second',
        ], $user);

        $this->assertNotEquals($article1->slug, $article2->slug);
    }

    #[Test]
    public function it_updates_an_article_and_creates_a_new_version(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();

        $article = $this->service->create([
            'title' => 'Original',
            'body_markdown' => 'Original content',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [],
            'commit_message' => 'Initial',
        ], $user);

        $updated = $this->service->update($article, [
            'title' => 'Updated Title',
            'body_markdown' => 'Updated content',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [],
        ], $user, 'Updated the title and content');

        $this->assertSame('Updated Title', $updated->title);
        $this->assertCount(2, $updated->fresh()->versions);
        $this->assertSame(2, $updated->versions()->orderByDesc('version_number')->first()->version_number);
    }

    #[Test]
    public function it_transfers_ownership(): void
    {
        $owner = User::factory()->create();
        $newOwner = User::factory()->create();
        $article = KbArticle::factory()->create(['owner_id' => $owner->id]);

        $this->service->transferOwnership($article, $newOwner);

        $this->assertTrue($article->fresh()->owner->is($newOwner));
    }

    #[Test]
    public function it_marks_article_as_reviewed(): void
    {
        $article = KbArticle::factory()->verified()->create(['reviewed_at' => null]);

        $this->service->markReviewed($article);

        $this->assertNotNull($article->fresh()->reviewed_at);
    }

    #[Test]
    public function it_changes_status(): void
    {
        $article = KbArticle::factory()->create();

        $this->service->changeStatus($article, 'verified');

        $this->assertSame('verified', $article->fresh()->status);
        $this->assertNotNull($article->fresh()->published_at);
    }

    #[Test]
    public function it_throws_when_slug_collisions_exceed_limit(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();

        // Create the base slug and 100 suffixed variants
        KbArticle::factory()->create(['slug' => 'same-title']);
        for ($i = 1; $i <= 100; $i++) {
            KbArticle::factory()->create(['slug' => "same-title-{$i}"]);
        }

        $this->expectException(\Exception::class);

        $this->service->create([
            'title' => 'Same Title',
            'body_markdown' => 'Content',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'tags' => [],
            'commit_message' => 'Should fail',
        ], $user);
    }
}
