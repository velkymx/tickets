<?php

namespace Tests\Feature\Integration;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbLifecycleTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function full_article_lifecycle(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        // Create
        $response = $this->actingAs($author)->post('/kb', [
            'title' => 'Architecture Overview',
            'body_markdown' => '# System Architecture',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'Initial creation',
            'tags' => [$tag->id],
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('kb_articles', ['title' => 'Architecture Overview']);
        $article = KbArticle::where('title', 'Architecture Overview')->first();

        // View
        $this->actingAs($author)->get("/kb/{$article->slug}")->assertOk();

        // Edit
        $this->actingAs($author)->put("/kb/{$article->slug}", [
            'title' => 'Architecture Overview v2',
            'body_markdown' => '# Updated Architecture',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'Updated architecture section',
            'tags' => [$tag->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('kb_articles', ['title' => 'Architecture Overview v2']);

        // Version history
        $this->actingAs($author)->get("/kb/{$article->slug}/history")->assertOk();
        $this->assertCount(2, $article->fresh()->versions);

        // Search
        $article->update(['status' => 'verified']);
        $this->get('/kb/search?q=Architecture')->assertOk()->assertSee('Architecture Overview v2');

        // Delete (author can't, admin can)
        $this->actingAs($author)->delete("/kb/{$article->slug}")->assertForbidden();

        $admin = User::factory()->create(['admin' => true]);
        $this->actingAs($admin)->delete("/kb/{$article->slug}")->assertRedirect();
        $this->assertSoftDeleted($article);

        // Restore
        $this->actingAs($admin)->post("/kb/admin/trashed/{$article->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($article);
    }
}
