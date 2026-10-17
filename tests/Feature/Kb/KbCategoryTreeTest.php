<?php

namespace Tests\Feature\Kb;

use App\Models\KbCategory;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class KbCategoryTreeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function admin_reaches_manager_from_kb_index(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($admin)->get('/kb');

        $response->assertOk();
        $response->assertSee(route('kb.admin.categories'));
    }

    #[Test]
    public function non_admin_does_not_see_manager_link(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        KbCategory::factory()->create(['name' => 'Guides']);

        $this->actingAs($author)->get('/kb')->assertDontSee(route('kb.admin.categories'));
        $this->get('/kb')->assertDontSee(route('kb.admin.categories'));
    }

    #[Test]
    public function admin_can_create_child_category(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);

        $this->actingAs($admin)->post('/kb/admin/categories', [
            'name' => 'Child',
            'parent_id' => $parent->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('kb_categories', ['name' => 'Child', 'parent_id' => $parent->id]);
    }

    #[Test]
    public function admin_can_move_category_to_top_level(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        $child = KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->put("/kb/admin/categories/{$child->id}", [
            'name' => 'Child',
            'parent_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('kb_categories', ['id' => $child->id, 'parent_id' => null]);
    }

    #[Test]
    public function category_cannot_be_its_own_parent(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = KbCategory::factory()->create(['name' => 'Solo']);

        $this->actingAs($admin)->put("/kb/admin/categories/{$category->id}", [
            'name' => 'Solo',
            'parent_id' => $category->id,
        ])->assertSessionHasErrors('parent_id');
    }

    #[Test]
    public function category_cannot_create_a_cycle(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        $child = KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->put("/kb/admin/categories/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $child->id,
        ])->assertSessionHasErrors('parent_id');
    }

    #[Test]
    public function model_rejects_cycle_written_around_the_controller(): void
    {
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        $child = KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);

        $parent->parent_id = $child->id;
        $parent->save();
    }

    #[Test]
    public function parent_with_children_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $this->actingAs($admin)->delete("/kb/admin/categories/{$parent->id}")->assertRedirect();
        $this->assertDatabaseHas('kb_categories', ['id' => $parent->id]);
    }

    #[Test]
    public function index_shows_child_indented_under_parent(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $response = $this->actingAs($admin)->get('/kb');

        $response->assertOk();
        $response->assertSee('Parent');
        $response->assertSee('-- Child');
    }

    #[Test]
    public function article_form_picker_shows_nested_options(): void
    {
        $author = User::factory()->create(['kb_role' => 'author']);
        $parent = KbCategory::factory()->create(['name' => 'Parent']);
        $child = KbCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $response = $this->actingAs($author)->get('/kb/create');

        $response->assertOk();
        $response->assertSee('-- Child');
        $response->assertSee('value="'.$child->id.'"', false);
    }
}
