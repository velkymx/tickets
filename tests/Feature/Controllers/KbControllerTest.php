<?php

namespace Tests\Feature\Controllers;

use App\Models\KbArticle;
use App\Models\KbArticlePermission;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_is_accessible_without_auth(): void
    {
        $article = KbArticle::factory()->verified()->public()->create(['title' => 'Public Runbook']);

        $this->get('/kb')
            ->assertOk()
            ->assertSee('Public Runbook')
            ->assertSee(route('kb.show', $article->slug), false);
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
    public function show_hides_internal_and_restricted_related_articles_from_guests(): void
    {
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->public()->create();
        $article->tags()->attach($tag);

        $internal = KbArticle::factory()->verified()->internal()->create(['title' => 'Internal Playbook']);
        $internal->tags()->attach($tag);

        $restricted = KbArticle::factory()->verified()->restricted()->create(['title' => 'Restricted Runbook']);
        $restricted->tags()->attach($tag);

        $this->get("/kb/{$article->slug}")
            ->assertOk()
            ->assertDontSee('Internal Playbook')
            ->assertDontSee('Restricted Runbook');
    }

    #[Test]
    public function show_lists_internal_related_articles_to_authenticated_users(): void
    {
        $user = User::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->public()->create();
        $article->tags()->attach($tag);

        $internal = KbArticle::factory()->verified()->internal()->create(['title' => 'Internal Playbook']);
        $internal->tags()->attach($tag);

        $this->actingAs($user)->get("/kb/{$article->slug}")
            ->assertOk()
            ->assertSee('Internal Playbook');
    }

    #[Test]
    public function show_hides_restricted_related_articles_from_unpermitted_users(): void
    {
        $user = User::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->public()->create();
        $article->tags()->attach($tag);

        $restricted = KbArticle::factory()->verified()->restricted()->create(['title' => 'Restricted Runbook']);
        $restricted->tags()->attach($tag);

        $this->actingAs($user)->get("/kb/{$article->slug}")
            ->assertOk()
            ->assertDontSee('Restricted Runbook');
    }

    #[Test]
    public function show_lists_restricted_related_articles_to_permitted_users(): void
    {
        $user = User::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->verified()->public()->create();
        $article->tags()->attach($tag);

        $restricted = KbArticle::factory()->verified()->restricted()->create(['title' => 'Restricted Runbook']);
        $restricted->tags()->attach($tag);
        KbArticlePermission::create(['article_id' => $restricted->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get("/kb/{$article->slug}")
            ->assertOk()
            ->assertSee('Restricted Runbook');
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

    public static function kbRolesThatCanCreate(): array
    {
        return ['reader' => [null], 'author' => ['author']];
    }

    #[Test]
    #[DataProvider('kbRolesThatCanCreate')]
    public function signed_in_user_can_open_create(?string $role): void
    {
        $user = User::factory()->create(['kb_role' => $role]);

        $this->actingAs($user)->get('/kb/create')->assertOk();
    }

    #[Test]
    public function create_view_mounts_the_markdown_editor(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);

        $response = $this->actingAs($author)->get('/kb/create');

        $response->assertOk();
        $response->assertSee('data-markdown-editor', false);
        $response->assertSee('data-autosave-id="kb-create"', false);
    }

    #[Test]
    public function edit_view_mounts_the_markdown_editor(): void
    {
        $owner = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();
        $article = KbArticle::factory()->create([
            'owner_id' => $owner->id,
            'user_id' => $owner->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($owner)->get("/kb/{$article->slug}/edit");

        $response->assertOk();
        $response->assertSee('data-markdown-editor', false);
        $response->assertSee('data-autosave-id="kb-edit-'.$article->slug.'"', false);
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

        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'title' => $article->title]);
    }

    #[Test]
    public function destroy_requires_admin(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $article = KbArticle::factory()->create(['owner_id' => $author->id]);

        $this->actingAs($author)->delete("/kb/{$article->slug}")->assertForbidden();

        $this->assertNotSoftDeleted($article);
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
