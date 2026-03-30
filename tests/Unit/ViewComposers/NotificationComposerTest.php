<?php

namespace Tests\Unit\ViewComposers;

use App\Models\User;
use App\ViewComposers\NotificationComposer;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NotificationComposerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_shares_notification_data_with_view(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $composer = new NotificationComposer;
        $view = $this->mock(View::class);

        $view->shouldReceive('with')
            ->once()
            ->with('latestNotifications', \Mockery::type('Illuminate\Database\Eloquent\Collection'));
        $view->shouldReceive('with')
            ->once()
            ->with('unreadNotificationCount', \Mockery::type('int'));

        $composer->compose($view);
    }

    #[Test]
    public function it_skips_when_no_user_authenticated(): void
    {
        $composer = new NotificationComposer;
        $view = $this->mock(View::class);

        $view->shouldNotReceive('with');

        $composer->compose($view);
    }
}
