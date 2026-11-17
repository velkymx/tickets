<?php

namespace Tests\Feature\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbAdminControllerTest extends TestCase
{
    use SeedsDatabase;

    public static function adminPages(): array
    {
        return [
            'categories' => ['/kb/admin/categories'],
            'tags' => ['/kb/admin/tags'],
            'trashed' => ['/kb/admin/trashed'],
        ];
    }

    #[Test]
    #[DataProvider('adminPages')]
    public function non_admin_cannot_access_admin_page(string $url): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);

        $this->actingAs($author)->get($url)->assertForbidden();
    }

    #[Test]
    public function admin_can_list_categories(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        KbCategory::factory()->create(['name' => 'Architecture']);

        $this->actingAs($admin)->get('/kb/admin/categories')
            ->assertOk()
            ->assertSee('Architecture');
    }

    #[Test]
    public function admin_can_create_category(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)->post('/kb/admin/categories', [
            'name' => 'New Category',
        ])->assertRedirect();

        $this->assertDatabaseHas('kb_categories', ['name' => 'New Category']);
    }

    #[Test]
    public function admin_cannot_create_category_with_duplicate_slug(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        KbCategory::factory()->create(['name' => 'Billing', 'slug' => 'billing']);

        $this->actingAs($admin)->post('/kb/admin/categories', [
            'name' => 'Billing!',
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, KbCategory::where('slug', 'billing')->count());
    }

    #[Test]
    public function admin_cannot_update_category_to_duplicate_slug(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        KbCategory::factory()->create(['name' => 'Billing', 'slug' => 'billing']);
        $category = KbCategory::factory()->create(['name' => 'Ops', 'slug' => 'ops']);

        $this->actingAs($admin)->put("/kb/admin/categories/{$category->id}", [
            'name' => 'Billing!',
        ])->assertSessionHasErrors('name');

        $this->assertSame('ops', $category->fresh()->slug);
    }

    #[Test]
    public function admin_cannot_create_tag_with_duplicate_slug(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        KbTag::factory()->create(['name' => 'php', 'slug' => 'php']);

        $this->actingAs($admin)->post('/kb/admin/tags', [
            'name' => 'PHP!',
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, KbTag::where('slug', 'php')->count());
    }

    #[Test]
    public function admin_can_update_category(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = KbCategory::factory()->create();

        $this->actingAs($admin)->put("/kb/admin/categories/{$category->id}", [
            'name' => 'Updated Name',
        ])->assertRedirect();

        $this->assertDatabaseHas('kb_categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function admin_cannot_delete_category_with_articles(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = KbCategory::factory()->create();
        KbArticle::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->delete("/kb/admin/categories/{$category->id}")->assertRedirect();
        $this->assertDatabaseHas('kb_categories', ['id' => $category->id]);
    }

    #[Test]
    public function admin_can_create_tags(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)->post('/kb/admin/tags', ['name' => 'php'])->assertRedirect();
        $this->assertDatabaseHas('kb_tags', ['name' => 'php']);
    }

    #[Test]
    public function admin_can_update_tags(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $tag = KbTag::factory()->create(['name' => 'php']);

        $this->actingAs($admin)->put("/kb/admin/tags/{$tag->id}", ['name' => 'PHP'])->assertRedirect();
        $this->assertDatabaseHas('kb_tags', ['id' => $tag->id, 'name' => 'PHP']);
    }

    #[Test]
    public function admin_sees_trashed_articles(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $article = KbArticle::factory()->create(['title' => 'Deleted Runbook']);
        $article->delete();

        $this->actingAs($admin)->get('/kb/admin/trashed')
            ->assertOk()
            ->assertSee('Deleted Runbook');
    }

    #[Test]
    public function admin_can_restore_trashed_article(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $article = KbArticle::factory()->create();
        $article->delete();

        $this->actingAs($admin)->post("/kb/admin/trashed/{$article->id}/restore")->assertRedirect();

        $this->assertNotSoftDeleted($article);
    }
}
