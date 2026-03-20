<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
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

        // Us dumb Americans can't handle millitary time
        $ampm = $time->format('H') > 12 ? ' ('.$time->format('g:i a').')' : '';

        // Remove region name and add a sample time
        $currenttime = $time->format('H:i').$ampm;

        return View('users.show', compact('user', 'alltickets', 'currenttime'));

    }

    public function edit()
    {

        $user = User::findOrFail(Auth::id());

        $timezones = $this->get_timezones();

        $themes = [
            '/css/bootstrap.min.css' => 'Default',
            '/css/bootstrap.darkly.min.css' => 'Darkly',
        ];

        return View('users.edit', compact('user', 'timezones', 'themes'));

    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
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

    public function watch($id)
    {

        $ticket = Ticket::findOrFail($id);

        $watch = TicketUserWatcher::where('ticket_id', $id)->where('user_id', Auth::id())->first();

        if ($watch) {

            $watch->delete();

            $message = 'Watch stopped for this ticket';

        } else {

            TicketUserWatcher::create(['user_id' => Auth::id(), 'ticket_id' => $id]);

            $message = 'Watch started for this ticket';

        }

        return $message;

    }

    private function get_timezones()
    {
        $regions = [
            'Africa' => \DateTimeZone::AFRICA,
            'America' => \DateTimeZone::AMERICA,
            'Antarctica' => \DateTimeZone::ANTARCTICA,
            'Aisa' => \DateTimeZone::ASIA,
            'Atlantic' => \DateTimeZone::ATLANTIC,
            'Europe' => \DateTimeZone::EUROPE,
            'Indian' => \DateTimeZone::INDIAN,
            'Pacific' => \DateTimeZone::PACIFIC,
        ];

        $timezones = [];
        foreach ($regions as $name => $mask) {
            $zones = \DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $timezone) {
                // Lets sample the time there right now
                $time = new \DateTime(null, new \DateTimeZone($timezone));

                // Us dumb Americans can't handle millitary time
                $ampm = $time->format('H') > 12 ? ' ('.$time->format('g:i a').')' : '';

                // Remove region name and add a sample time
                $timezones[$name][$timezone] = substr($timezone, strlen($name) + 1).' - '.$time->format('H:i').$ampm;
            }
        }

        return $timezones;
    }
}
