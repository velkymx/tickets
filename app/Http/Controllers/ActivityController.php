<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Auth::user()
            ->notifications()
            ->when($request->string('filter')->toString() !== '', function ($query) use ($request) {
                $filter = $request->string('filter')->toString();
                $type = match ($filter) {
                    'mentions' => 'mention',
                    'watching' => 'watching',
                    'assigned' => 'assigned',
                    'replies' => 'reply',
                    default => null,
                };

                if ($type) {
                    $query->where('data', 'like', '%"type":"'.$type.'"%');
                }
            })
            ->latest()
            ->paginate(20);

        return view('activity.index', [
            'notifications' => $notifications,
            'filter' => $request->string('filter')->toString() ?: 'all',
        ]);
    }

    public function read(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return redirect('/activity');
    }

    public function readAll(): RedirectResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect('/activity');
    }
}
