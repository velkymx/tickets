<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\ReplyNotification;
use App\Notifications\WatcherNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSee('Mention body');
        $response->assertDontSee('Reply body');
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
}
