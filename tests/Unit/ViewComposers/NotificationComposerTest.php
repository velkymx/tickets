<?php

namespace Tests\Unit\ViewComposers;

use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationComposerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function layout_receives_latest_five_and_unread_count(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 7) as $i) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => MentionNotification::class,
                'data' => ['message' => "Note {$i}"],
                'read_at' => $i <= 2 ? now() : null,
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
        $response->assertSee('5 unread', false);
        $response->assertSeeInOrder(['Note 7', 'Note 6', 'Note 5', 'Note 4', 'Note 3']);
        $response->assertDontSee('Note 2');
    }

    #[Test]
    public function guest_layout_gets_no_notification_data(): void
    {
        $this->get('/login')->assertViewMissing('latestNotifications');
    }
}
