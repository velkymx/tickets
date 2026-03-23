<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\ReplyNotification;
use App\Notifications\WatcherNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ActivityControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/activity');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function index_lists_the_current_users_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Check the deploy.', 'http://example.com/tickets/142'));
        $other->notifyNow(new WatcherNotification('Ticket', 'Unrelated update', 'http://example.com/tickets/999'));

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
        $response->assertViewIs('activity.index');
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 1;
        });
        $response->assertSee('Check the deploy.');
        $response->assertDontSee('Unrelated update');
    }

    #[Test]
    public function index_filters_notifications_by_type(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Mention body', 'http://example.com/tickets/142'));
        $user->notifyNow(new ReplyNotification($actor, 142, 56, 'Reply body', 'http://example.com/tickets/142#note_56'));

        $response = $this->actingAs($user)->get('/activity?filter=mentions');

        $response->assertOk();
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 1
                && ($notifications->first()->data['excerpt'] ?? null) === 'Mention body';
        });
    }

    #[Test]
    public function index_renders_the_activity_center_layout(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Check the deploy.', 'http://example.com/tickets/142'));

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
        $response->assertSee('Activity Center');
        $response->assertSee('Mark all');
        $response->assertSee('All');
        $response->assertSee('Mentions');
        $response->assertSee('Watching');
        $response->assertSee('Replies');
        $response->assertSee('Unread');
    }

    #[Test]
    public function index_uses_theme_aware_notification_card_backgrounds(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Check the deploy.', 'http://example.com/tickets/142'));

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
        $response->assertSee('bg-body', false);
        $response->assertDontSee('bg-white', false);
    }

    #[Test]
    public function read_marks_a_single_notification_as_read(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Check the deploy.', 'http://example.com/tickets/142'));
        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)->post("/activity/read/{$notification->id}");

        $response->assertRedirect('/activity');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function read_all_marks_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        $user->notifyNow(new MentionNotification($actor, 142, 55, 'Check the deploy.', 'http://example.com/tickets/142'));
        $user->notifyNow(new ReplyNotification($actor, 142, 56, 'I pushed the fix.', 'http://example.com/tickets/142#note_56'));

        $response = $this->actingAs($user)->post('/activity/read-all');

        $response->assertRedirect('/activity');
        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    #[Test]
    public function authenticated_layout_renders_notification_bell_dropdown(): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Sarah']);

        foreach (range(1, 6) as $index) {
            $user->notifyNow(new MentionNotification(
                $actor,
                140 + $index,
                50 + $index,
                "Mention {$index}",
                'http://example.com/tickets/'.(140 + $index)
            ));
        }

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
        $response->assertSee('id="notificationsDropdown"', false);
        $response->assertSee('fas fa-bell', false);
        $response->assertSee('notification-count-inline', false);
        $response->assertDontSee('translate-middle badge', false);
        $response->assertSee('6 unread');
        $response->assertSee('View all');
        $response->assertSee('/activity', false);
        $response->assertSee('Mention 6');
        $response->assertSee('Mention 2');
        $response->assertViewHas('notifications', function ($notifications) {
            return $notifications->count() === 6;
        });
    }

    #[Test]
    public function authenticated_layout_renders_outlined_bell_when_there_are_no_unread_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
        $response->assertSee('far fa-bell', false);
        $response->assertDontSee('fas fa-bell', false);
    }
}
