<?php

namespace Tests\Feature\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_is_accessible_without_auth(): void
    {
        KbArticle::factory()->verified()->public()->create();

        $response = $this->get('/kb');
        $response->assertOk();
    }

    #[Test]
    public function index_shows_only_public_verified_for_guests(): void
    {
        KbArticle::factory()->verified()->public()->create(['title' => 'Public Article']);
        KbArticle::factory()->verified()->internal()->create(['title' => 'Internal Article']);

        $response = $this->get('/kb');
        $response->assertSee('Public Article');
        $response->assertDontSee('Internal Article');
    }

    #[Test]
    public function show_displays_public_article_without_auth(): void
    {
        $article = KbArticle::factory()->verified()->public()->create();

        $response = $this->get("/kb/{$article->slug}");
        $response->assertOk();
        $response->assertSee($article->title);
    }

    #[Test]
    public function show_returns_403_for_restricted_article_without_permission(): void
    {
        $user = User::factory()->create();
        $article = KbArticle::factory()->verified()->restricted()->create();

        $response = $this->actingAs($user)->get("/kb/{$article->slug}");
        $response->assertForbidden();
    }

    #[Test]
    public function show_returns_403_for_deprecated_public_article(): void
    {
        $article = KbArticle::factory()->public()->create(['status' => 'deprecated']);

        $response = $this->get("/kb/{$article->slug}");
        $response->assertForbidden();
    }

    #[Test]
    public function show_sanitizes_html_in_article_body(): void
    {
        $article = KbArticle::factory()->verified()->public()->create([
            'body_html' => '<p>Safe content</p><script>alert("xss")</script>',
        ]);

        $response = $this->get("/kb/{$article->slug}");
        $response->assertOk();
        $response->assertSee('Safe content');
        $response->assertDontSee('<script>', false);
    }

    #[Test]
    public function create_requires_auth_and_author_role(): void
    {
        $this->get('/kb/create')->assertRedirect('/login');

        $reader = User::factory()->create(['kb_role' => null]);
        $this->actingAs($reader)->get('/kb/create')->assertForbidden();

        $author = User::factory()->create(['kb_role' => 'author']);
        $this->actingAs($author)->get('/kb/create')->assertOk();
    }

    #[Test]
    public function store_creates_article_and_redirects(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        $response = $this->actingAs($author)->post('/kb', [
            'title' => 'New Article',
            'body_markdown' => '# Hello',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'Initial',
            'tags' => [$tag->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kb_articles', ['title' => 'New Article']);
    }

    #[Test]
    public function update_requires_ownership_or_permission(): void
    {
        $owner = User::factory()->create(['kb_role' => 'author']);
        $stranger = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->create([
            'owner_id' => $owner->id,
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);
        $article->tags()->attach($tag);

        $this->actingAs($stranger)->put("/kb/{$article->slug}", [
            'title' => 'Hacked',
            'body_markdown' => 'Nope',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'Evil edit',
            'tags' => [$tag->id],
        ])->assertForbidden();
    }

    #[Test]
    public function destroy_requires_admin(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id]);

        $this->actingAs($author)->delete("/kb/{$article->slug}")->assertForbidden();
    }

    #[Test]
    public function search_returns_matching_articles(): void
    {
        KbArticle::factory()->verified()->public()->create(['title' => 'Laravel Guide']);
        KbArticle::factory()->verified()->public()->create(['title' => 'React Guide']);

        $response = $this->get('/kb/search?q=Laravel');
        $response->assertOk();
        $response->assertSee('Laravel Guide');
        $response->assertDontSee('React Guide');
    }

    #[Test]
    public function author_can_upload_attachment(): void
    {
        Storage::fake('public');

        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id, 'user_id' => $author->id]);

        $response = $this->actingAs($author)->post("/kb/{$article->slug}/attachments", [
            'file' => UploadedFile::fake()->image('diagram.png'),
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'filename', 'url', 'mime_type', 'isImage']);
        $this->assertDatabaseHas('kb_article_attachments', ['article_id' => $article->id, 'filename' => 'diagram.png']);
    }

    #[Test]
    public function non_owner_cannot_upload_attachment(): void
    {
        $stranger = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create();

        $this->actingAs($stranger)->post("/kb/{$article->slug}/attachments", [
            'file' => UploadedFile::fake()->image('hack.png'),
        ])->assertForbidden();
    }

    #[Test]
    public function max_20_attachments_enforced(): void
    {
        Storage::fake('public');

        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id, 'user_id' => $author->id]);

        // Create 20 existing attachments
        for ($i = 0; $i < 20; $i++) {
            $article->attachments()->create([
                'user_id' => $author->id,
                'filename' => "file{$i}.png",
                'path' => "kb-attachments/{$article->id}/file{$i}.png",
                'mime_type' => 'image/png',
                'size' => 1024,
            ]);
        }

        $response = $this->actingAs($author)->post("/kb/{$article->slug}/attachments", [
            'file' => UploadedFile::fake()->image('one-too-many.png'),
        ]);

        $response->assertStatus(422);
    }
}
