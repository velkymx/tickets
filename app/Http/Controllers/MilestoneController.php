<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MilestoneController extends Controller
{

    public function index()
    {

        $milestones = Milestone::orderBy('name')->get();

        return view('milestone.index', compact('milestones'));

    }

    public function print(Request $request)
    {

        $milestone = Milestone::with('tickets.project')->findOrFail($request->id);

        $projects = [];

        foreach ($milestone->tickets as $tic) {
            $projects[$tic->project->id] = $tic->project->name;
        }

        $types = Type::all();

        return view('milestone.print', compact('milestone', 'types', 'projects'));

    }

    public function getShow(Request $request)
    {

        $milestone = Milestone::with(['watchers.user', 'tickets'])->findOrFail($request->id);

        $tmpcodes = Status::get();

        $statuscodes = [];

        foreach ($tmpcodes as $code) {

            $statuscodes[$code->id] = [
                'name' => $code->name,
                'slug' => Str::slug($code->name, '_'),
            ];

        }

        $completed = 0;

        $completed = $milestone->tickets()->whereIn('status_id', ['5', '8', '9'])->count();

        $total = $milestone->tickets->count();

        $percent = 0;

        if ($total !== 0 && $completed !== 0) {

            $percent = (round($completed / $total, 2) * 100);

        }

        if ($completed == $total) {

            $percent = 100;

        }

        return view('milestone.show', compact('milestone', 'statuscodes', 'completed', 'percent'));

    }

    public function create()
    {

        $users = User::pluck('name', 'id');

        return view('milestone.create', compact('users'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scrummaster_user_id' => ['nullable', 'integer'],
            'owner_user_id' => ['nullable', 'integer'],
            'start_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at', 'after_or_equal:due_at'],
        ]);

        $milestone = Milestone::findOrFail($id);

        $milestone->fill($validatedData);

        $milestone->save();

        return redirect('/milestone/show/'.$milestone->id)
            ->with('success', 'Milestone "'.$milestone->name.'" updated successfully!');
    }

    public function edit(Request $request, $id)
    {
        $milestone = Milestone::findOrFail($request->id);

        $users = User::pluck('name', 'id');

        return view('milestone.edit', compact('milestone', 'users'));
    }

    public function store(Request $request)
    {

        $post = $request->toArray();

        foreach (['start_at', 'due_at', 'end_at'] as $date) {

            if (isset($post[$date]) && $post[$date] != '') {

                $post[$date] = date('Y-m-d', strtotime($post[$date]));

            } else {

                $post[$date] = null;
            }
        }

        if ($request->id == 'new') {

            $post['active'] = 1;

            Milestone::create($post);

        } else {

            $milestone = Milestone::findOrFail($request->id);

            $milestone->update($post);

        }

        return redirect('milestone');
    }

    public function toggleWatcher($id)
    {
        $milestone = Milestone::findOrFail($id);
        $watcher = MilestoneWatcher::where('milestone_id', $id)->where('user_id', Auth::id())->first();

        if ($watcher) {
            $watcher->delete();
        } else {
            MilestoneWatcher::create([
                'milestone_id' => $id,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->back();
    }

    public function report($id)
    {
        // Any authenticated user can view milestone reports
        $milestone = Milestone::with(['owner', 'scrummaster'])->findOrFail($id);
        $tickets = $milestone->tickets()
            ->with(['status', 'type', 'assignee', 'notes.user'])
            ->get();

        $closedStatusIds = Status::closedStatusIds();
        $totalTickets = $tickets->count();
        $completedTickets = $tickets->whereIn('status_id', $closedStatusIds)->count();
        $openTickets = $tickets->whereNotIn('status_id', $closedStatusIds)->count();

        $totalStoryPoints = $tickets->sum('storypoints');
        $completedStoryPoints = $tickets->whereIn('status_id', $closedStatusIds)->sum('storypoints');
        $remainingStoryPoints = $totalStoryPoints - $completedStoryPoints;
        $completionPercentage = $totalStoryPoints > 0 ? round(($completedStoryPoints / $totalStoryPoints) * 100) : 0;

        $statusBreakdown = $tickets->groupBy('status_id')->map(function ($group) {
            return [
                'name' => $group->first()->status->name ?? 'Unknown',
                'count' => $group->count(),
            ];
        })->values();

        $typeBreakdown = $tickets->groupBy('type_id')->map(function ($group) {
            return [
                'name' => $group->first()->type->name ?? 'Unknown',
                'count' => $group->count(),
            ];
        })->values();

        $teamHours = $tickets->flatMap(function ($ticket) {
            return $ticket->notes->map(function ($note) use ($ticket) {
                return [
                    'user_id' => $note->user_id,
                    'user_name' => $note->user->name ?? 'Unknown',
                    'hours' => $note->hours,
                    'ticket_id' => $ticket->id,
                ];
            });
        })->groupBy('user_id')->map(function ($notes, $userId) {
            return [
                'user_name' => $notes->first()['user_name'],
                'total_hours' => $notes->sum('hours'),
                'ticket_count' => $notes->unique('ticket_id')->count(),
            ];
        })->values();

        $ticketDetails = $tickets->map(function ($ticket) {
            $loggedHours = $ticket->notes->sum('hours');

            return [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status->name ?? 'Unknown',
                'type' => $ticket->type->name ?? 'Unknown',
                'assignee' => $ticket->assignee->name ?? 'Unassigned',
                'storypoints' => $ticket->storypoints ?? 0,
                'logged_hours' => $loggedHours,
            ];
        });

        $startDate = $milestone->start_at ? Carbon::parse($milestone->start_at) : null;
        $endDate = $milestone->due_at ? Carbon::parse($milestone->due_at) : null;
        $duration = $startDate && $endDate ? $startDate->diffInDays($endDate) : 0;

        $burndownData = [];
        if ($startDate && $endDate) {
            $sprintEnd = $endDate;

            $idealBurndown = [];
            $actualBurndown = [];
            $dates = [];

            $daysInSprint = $startDate->diffInDays($sprintEnd) + 1;
            $pointsPerDay = $totalStoryPoints / max($daysInSprint, 1);
            $pointsPerDay = $pointsPerDay > 0 ? $pointsPerDay : 0;

            for ($i = 0; $i <= $daysInSprint; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dates[] = $date->format('M j');
                $idealBurndown[] = max(0, $totalStoryPoints - ($pointsPerDay * $i));
            }

            $closedAtDates = $tickets->whereIn('status_id', $closedStatusIds)
                ->get()
                ->map(function ($ticket) {
                    $ticket->closed_at = $ticket->closed_at ?? $ticket->updated_at;

                    return $ticket;
                })
                ->filter(fn ($ticket) => $ticket->closed_at)
                ->groupBy(fn ($ticket) => $ticket->closed_at->format('Y-m-d'))
                ->sortKeys();

            $cumulativeClosed = 0;
            $runningDates = [];
            foreach ($closedAtDates as $dateStr => $ticketsOnDate) {
                $cumulativeClosed += $ticketsOnDate->sum('storypoints');
                $runningDates[$dateStr] = $cumulativeClosed;
            }

            for ($i = 0; $i <= $daysInSprint; $i++) {
                $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                $actualBurndown[] = max(0, $totalStoryPoints - ($runningDates[$date] ?? 0));
            }

            $burndownData = [
                'labels' => $dates,
                'ideal' => $idealBurndown,
                'actual' => $actualBurndown,
            ];
        }

        return view('milestone.report', compact(
            'milestone',
            'totalTickets',
            'completedTickets',
            'openTickets',
            'totalStoryPoints',
            'completedStoryPoints',
            'remainingStoryPoints',
            'completionPercentage',
            'statusBreakdown',
            'typeBreakdown',
            'teamHours',
            'ticketDetails',
            'duration',
            'burndownData'
        ));
    }
}
