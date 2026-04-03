<?php

namespace Tests\Feature\Controllers;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\KbTag;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbAdminControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['kb_role' => 'author']);

        $this->actingAs($user)->get('/kb/admin/categories')->assertForbidden();
        $this->actingAs($user)->get('/kb/admin/tags')->assertForbidden();
        $this->actingAs($user)->get('/kb/admin/trashed')->assertForbidden();
    }

    #[Test]
    public function admin_can_list_categories(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        KbCategory::factory()->create(['name' => 'Architecture']);

        $response = $this->actingAs($admin)->get('/kb/admin/categories');
        $response->assertOk();
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
    public function admin_can_create_and_update_tags(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)->post('/kb/admin/tags', ['name' => 'php'])->assertRedirect();
        $this->assertDatabaseHas('kb_tags', ['name' => 'php']);

        $tag = KbTag::where('name', 'php')->first();
        $this->actingAs($admin)->put("/kb/admin/tags/{$tag->id}", ['name' => 'PHP'])->assertRedirect();
        $this->assertDatabaseHas('kb_tags', ['id' => $tag->id, 'name' => 'PHP']);
    }

    #[Test]
    public function admin_can_view_and_restore_trashed_articles(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $article = KbArticle::factory()->create();
        $article->delete();

        $this->actingAs($admin)->get('/kb/admin/trashed')->assertOk();
        $this->actingAs($admin)->post("/kb/admin/trashed/{$article->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($article);
    }
}
