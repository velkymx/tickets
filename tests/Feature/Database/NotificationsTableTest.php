<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationsTableTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_creates_the_notifications_table_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('notifications', [
            'id',
            'type',
            'notifiable_type',
            'notifiable_id',
            'data',
            'read_at',
            'created_at',
            'updated_at',
        ]));
    }

    #[Test]
    public function users_can_receive_database_notifications(): void
    {
        $user = User::factory()->create();

        $notification = new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toDatabase(object $notifiable): array
            {
                return [
                    'message' => 'Mentioned in a ticket note.',
                ];
            }
        };

        $user->notify($notification);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => $notification::class,
        ]);
    }
}
