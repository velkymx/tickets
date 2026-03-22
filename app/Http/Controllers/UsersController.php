<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function show($id)
    {

        $user = User::findOrFail($id);

        $statuses = Status::pluck('name', 'id');

        $tickets = Ticket::where('user_id2', $id)
            ->with('status')
            ->get()
            ->groupBy(fn ($ticket) => $ticket->status->name);

        $alltickets = [];
        foreach ($tickets as $statusName => $ticketGroup) {
            $alltickets[$statusName] = $ticketGroup;
        }

        $timezone = $user->timezone ?? config('app.timezone');
        $time = new \DateTime(null, new \DateTimeZone($timezone));

        // Add 12-hour format time for display
        $ampm = $time->format('H') > 12 ? ' ('.$time->format('g:i a').')' : '';

        // Add sample time for current timezone
        $currenttime = $time->format('H:i').$ampm;

        return View('users.show', compact('user', 'alltickets', 'currenttime'));

    }

    public function edit()
    {

        $user = User::findOrFail(Auth::id());

        $timezones = $this->get_timezones();

        $themes = [
            'simplex' => 'Default (Light)',
            'darkly' => 'Darkly (Dark)',
        ];

        return View('users.edit', compact('user', 'timezones', 'themes'));

    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.Auth::id(),
            'phone' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:100',
            'theme' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);

        $user = User::findOrFail(Auth::id());
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->timezone = $request->timezone;
        $user->theme = $request->theme;
        $user->title = $request->title;
        $user->bio = $request->bio;

        $user->save();

        return redirect('users/'.Auth::id())->with('info_message', 'Profile Changes Saved');

    }

    private function get_timezones()
    {
        $regions = [
            'Africa' => \DateTimeZone::AFRICA,
            'America' => \DateTimeZone::AMERICA,
            'Antarctica' => \DateTimeZone::ANTARCTICA,
            'Asia' => \DateTimeZone::ASIA,
            'Atlantic' => \DateTimeZone::ATLANTIC,
            'Europe' => \DateTimeZone::EUROPE,
            'Indian' => \DateTimeZone::INDIAN,
            'Pacific' => \DateTimeZone::PACIFIC,
        ];

        $timezones = [];
        foreach ($regions as $name => $mask) {
            $zones = \DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $timezone) {
                // Get current time in this timezone
                $time = new \DateTime(null, new \DateTimeZone($timezone));

                // Add 12-hour format for display
                $ampm = $time->format('H') > 12 ? ' ('.$time->format('g:i a').')' : '';

                // Format timezone name with current time
                $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1).' - '.$time->format('H:i').$ampm;
            }
        }

        return $timezones;
    }
}
