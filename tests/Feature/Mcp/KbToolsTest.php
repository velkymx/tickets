<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\TicketsServer;
use App\Mcp\Tools\CreateKbArticleTool;
use App\Mcp\Tools\GetKbArticleTool;
use App\Mcp\Tools\GetKbLookupsTool;
use App\Mcp\Tools\KbSearchTool;
use App\Mcp\Tools\UpdateKbArticleTool;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbToolsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function lookups_tool_returns_categories_and_tags(): void
    {
        $user = User::factory()->create();
        $category = KbCategory::factory()->create(['name' => 'Runbooks']);
        $tag = KbTag::factory()->create(['name' => 'deploy']);

        $response = TicketsServer::actingAs($user)->tool(GetKbLookupsTool::class, []);

        $response->assertOk();
        $response->assertSee('Runbooks');
        $response->assertSee('deploy');
    }

    #[Test]
    public function search_tool_returns_visible_articles_and_hides_others_drafts(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $visible = KbArticle::factory()->create([
            'title' => 'Deploy Guide', 'status' => 'verified', 'visibility' => 'internal',
        ]);
        $hiddenDraft = KbArticle::factory()->create([
            'title' => 'Secret Draft', 'status' => 'draft', 'visibility' => 'internal', 'user_id' => $author->id, 'owner_id' => $author->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(KbSearchTool::class, ['query' => 'e']);

        $response->assertOk();
        $response->assertSee('Deploy Guide');
        $response->assertDontSee('Secret Draft');
    }

    #[Test]
    public function get_article_tool_returns_a_visible_article_by_slug(): void
    {
        $user = User::factory()->create();
        $article = KbArticle::factory()->create([
            'title' => 'Runbook A', 'slug' => 'runbook-a', 'status' => 'verified', 'visibility' => 'internal',
        ]);

        $response = TicketsServer::actingAs($user)->tool(GetKbArticleTool::class, ['slug' => 'runbook-a']);

        $response->assertOk();
        $response->assertSee('Runbook A');
    }

    #[Test]
    public function get_article_tool_denies_an_invisible_article(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $article = KbArticle::factory()->create([
            'slug' => 'secret', 'status' => 'draft', 'visibility' => 'internal', 'user_id' => $author->id, 'owner_id' => $author->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(GetKbArticleTool::class, ['slug' => 'secret']);

        $response->assertHasErrors(['Article not found.']);
    }

    #[Test]
    public function create_article_tool_creates_and_assigns_owner(): void
    {
        $user = User::factory()->create();
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();

        $response = TicketsServer::actingAs($user)->tool(CreateKbArticleTool::class, [
            'title' => 'New Article',
            'body_markdown' => '# Hello',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'initial',
            'tags' => [$tag->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('kb_articles', [
            'title' => 'New Article',
            'owner_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function update_article_tool_denies_non_owner(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->create([
            'status' => 'verified', 'visibility' => 'internal',
            'user_id' => $owner->id, 'owner_id' => $owner->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(UpdateKbArticleTool::class, [
            'id' => $article->id,
            'title' => 'Hijacked',
            'body_markdown' => 'x',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'edit',
            'tags' => [$tag->id],
        ]);

        $response->assertHasErrors(['You cannot edit this article.']);
    }

    #[Test]
    public function update_article_tool_updates_and_versions_for_owner(): void
    {
        $user = User::factory()->create();
        $category = KbCategory::factory()->create();
        $tag = KbTag::factory()->create();
        $article = KbArticle::factory()->create([
            'status' => 'verified', 'visibility' => 'internal',
            'user_id' => $user->id, 'owner_id' => $user->id,
        ]);

        $response = TicketsServer::actingAs($user)->tool(UpdateKbArticleTool::class, [
            'id' => $article->id,
            'title' => 'Updated Title',
            'body_markdown' => 'new body',
            'category_id' => $category->id,
            'visibility' => 'internal',
            'commit_message' => 'edit',
            'tags' => [$tag->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('kb_articles', ['id' => $article->id, 'title' => 'Updated Title']);
        $this->assertDatabaseHas('kb_article_versions', ['article_id' => $article->id, 'commit_message' => 'edit']);
    }
}
