<?php

namespace App\ViewComposers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $view->with('latestNotifications', $user->notifications()->latest()->limit(5)->get());
        $view->with('unreadNotificationCount', $user->unreadNotifications()->count());
    }
}
