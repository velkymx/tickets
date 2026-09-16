<?php

namespace Tests\Feature\Api;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbControllerTest extends TestCase
{
    use SeedsDatabase;

    protected string $token;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $plainToken = 'test-api-token-that-is-long-enough-for-sha256';
        $this->token = $plainToken;

        $this->user = User::factory()->create([
            'api_token' => hash('sha256', $plainToken),
        ]);
    }

    protected function apiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    public function kb_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/kb/articles')->assertStatus(401);
    }

    #[Test]
    public function lookups_returns_categories_and_tags(): void
    {
        KbCategory::factory()->create(['name' => 'Runbooks']);
        KbTag::factory()->create(['name' => 'deploy']);

        $response = $this->getJson('/api/v1/kb/lookups', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertSee('Runbooks')
            ->assertSee('deploy');
    }

    #[Test]
    public function index_returns_visible_articles_and_hides_others_drafts(): void
    {
        $author = User::factory()->create();

        KbArticle::factory()->create([
            'title' => 'Deploy Guide', 'status' => 'verified', 'visibility' => 'internal',
        ]);
        KbArticle::factory()->create([
            'title' => 'Secret Draft', 'status' => 'draft', 'visibility' => 'internal',
            'user_id' => $author->id, 'owner_id' => $author->id,
        ]);

        $response = $this->getJson('/api/v1/kb/articles?query=e', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertSee('Deploy Guide')
            ->assertDontSee('Secret Draft');
    }

    #[Test]
    public function show_returns_a_visible_article_by_slug(): void
    {
        KbArticle::factory()->create([
            'title' => 'Runbook A', 'slug' => 'runbook-a', 'status' => 'verified', 'visibility' => 'internal',
        ]);

        $response = $this->getJson('/api/v1/kb/articles/runbook-a', $this->apiHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'runbook-a')
            ->assertJsonPath('data.title', 'Runbook A');
    }

    #[Test]
    public function show_returns_404_for_an_invisible_article(): void
    {
        $author = User::factory()->create();
        KbArticle::factory()->create([
            'slug' => 'secret', 'status' => 'draft', 'visibility' => 'internal',
            'user_id' => $author->id, 'owner_id' => $author->id,
        ]);

        $this->getJson('/api/v1/kb/articles/secret', $this->apiHeaders())->assertStatus(404);
    }

    #[Test]
    public function store_creates_an_article_owned_by_the_caller(): void
    {
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        $response = $this->postJson('/api/v1/kb/articles', [
            'title' => 'New Article',
            'body_markdown' => '# Hello',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'initial',
            'tags' => [$tag->id],
        ], $this->apiHeaders());

        $response->assertStatus(201);
        $this->assertDatabaseHas('kb_articles', [
            'title' => 'New Article',
            'owner_id' => $this->user->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function update_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->create([
            'status' => 'verified', 'visibility' => 'internal',
            'user_id' => $owner->id, 'owner_id' => $owner->id,
        ]);

        $response = $this->putJson("/api/v1/kb/articles/{$article->id}", [
            'title' => 'Hijacked',
            'body_markdown' => 'x',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'edit',
            'tags' => [$tag->id],
        ], $this->apiHeaders());

        $response->assertStatus(403);
    }

    #[Test]
    public function update_edits_and_versions_for_the_owner(): void
    {
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->create([
            'status' => 'verified', 'visibility' => 'internal',
            'user_id' => $this->user->id, 'owner_id' => $this->user->id,
        ]);

        $response = $this->putJson("/api/v1/kb/articles/{$article->id}", [
            'title' => 'Updated Title',
            'body_markdown' => 'new body',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'edit',
            'tags' => [$tag->id],
        ], $this->apiHeaders());

        $response->assertStatus(200);
        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'title' => 'Updated Title']);
        $this->assertDatabaseHas('kb_article_versions', ['article_id' => $article->id, 'commit_message' => 'edit']);
    }
}
