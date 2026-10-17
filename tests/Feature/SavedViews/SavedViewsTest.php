<?php

namespace Tests\Feature\SavedViews;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\SavedView;
use App\Models\Ticket;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SavedViewsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function guests_cannot_manage_views(): void
    {
        $before = SavedView::count();

        $this->post('/saved-views', ['name' => 'Mine', 'query' => 'is:open'])->assertRedirect('/login');
        $this->delete('/saved-views/1')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame($before, SavedView::count());
        $this->assertDatabaseMissing('saved_views', ['name' => 'Mine']);
    }

    #[Test]
    public function user_can_save_and_see_view_on_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/saved-views', [
            'name' => 'My open',
            'query' => 'assignee:me is:open',
        ])->assertRedirect();

        $this->assertDatabaseHas('saved_views', ['user_id' => $user->id, 'name' => 'My open']);

        $this->actingAs($user)->get('/tickets')->assertOk()->assertSee('My open');
    }

    #[Test]
    public function view_requires_name_and_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/saved-views', ['name' => '', 'query' => ''])->assertSessionHasErrors(['name', 'query']);
    }

    #[Test]
    public function user_can_delete_own_view(): void
    {
        $user = User::factory()->create();
        $view = SavedView::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete("/saved-views/{$view->id}")->assertRedirect();
        $this->assertDatabaseMissing('saved_views', ['id' => $view->id]);
    }

    #[Test]
    public function user_cannot_delete_another_users_view(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $view = SavedView::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->delete("/saved-views/{$view->id}")->assertForbidden();
        $this->assertDatabaseHas('saved_views', ['id' => $view->id]);
    }

    #[Test]
    public function applying_a_view_filters_the_list(): void
    {
        $user = User::factory()->create();
        $mine = Ticket::factory()->create(['user_id2' => $user->id, 'subject' => 'My urgent fix']);
        Ticket::factory()->create(['subject' => 'Someone else boring ticket']);

        $response = $this->actingAs($user)->get('/tickets?q='.urlencode('assignee:me'));

        $response->assertOk();
        $response->assertSee($mine->subject);
        $response->assertDontSee('Someone else boring ticket');
    }

    #[Test]
    public function duplicate_names_are_rejected_per_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post('/saved-views', ['name' => 'My open', 'query' => 'is:open'])->assertRedirect();
        $this->actingAs($user)->post('/saved-views', ['name' => 'My open', 'query' => 'is:closed'])->assertSessionHasErrors('name');

        $this->actingAs($other)->post('/saved-views', ['name' => 'My open', 'query' => 'is:open'])->assertRedirect();
    }

    #[Test]
    public function chips_render_on_board_project_and_milestone_pages(): void
    {
        $user = User::factory()->create();
        SavedView::factory()->create(['user_id' => $user->id, 'name' => 'Everywhere chips']);
        $project = Project::factory()->create();
        $milestone = Milestone::factory()->create();

        $this->actingAs($user)->get('/tickets/board')->assertOk()->assertSee('Everywhere chips');
        $this->actingAs($user)->get("/projects/show/{$project->id}")->assertOk()->assertSee('Everywhere chips');
        $this->actingAs($user)->get("/milestone/show/{$milestone->id}")->assertOk()->assertSee('Everywhere chips');
    }
}
