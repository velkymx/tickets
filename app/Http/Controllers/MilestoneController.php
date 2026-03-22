<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MilestoneController extends Controller
{
    public function index()
    {

        $milestones = Milestone::orderBy('name')->paginate(20);

        return view('milestone.index', compact('milestones'));

    }

    public function print($id)
    {

        $milestone = Milestone::with([
            'tickets' => function ($q) {
                $q->with(['project', 'type', 'status', 'assignee']);
            },
        ])->findOrFail($id);

        $this->authorize('view', $milestone);

        $projects = [];

        foreach ($milestone->tickets as $tic) {
            if ($tic->project) {
                $projects[$tic->project->id] = $tic->project->name;
            }
        }

        $types = Type::all();

        return view('milestone.print', compact('milestone', 'types', 'projects'));

    }

    public function getShow($id)
    {

        $milestone = Milestone::with([
            'watchers.user',
            'tickets' => function ($q) {
                $q->with(['project', 'type', 'status', 'importance', 'assignee', 'notes' => function ($noteQ) {
                    $noteQ->where('hide', 0)->where('notetype', 'message');
                }]);
            },
        ])->findOrFail($id);

        $this->authorize('view', $milestone);

        $tmpcodes = Status::get();

        $statuscodes = [];

        foreach ($tmpcodes as $code) {

            $statuscodes[$code->id] = [
                'name' => $code->name,
                'slug' => Str::slug($code->name, '_'),
            ];

        }

        $completed = 0;

        $completed = $milestone->tickets()->whereIn('status_id', Status::closedStatusIds())->count();

        $total = $milestone->tickets->count();

        $percent = 0;

        if ($total > 0) {

            $percent = (round($completed / $total, 2) * 100);

            if ($completed == $total) {

                $percent = 100;

            }

        }

        return view('milestone.show', compact('milestone', 'statuscodes', 'completed', 'percent'));

    }

    public function create()
    {

        $users = User::pluck('name', 'id');

        return view('milestone.create', compact('users'));
    }

    public function update(UpdateMilestoneRequest $request, $id)
    {
        $validatedData = $request->validated();

        $milestone = Milestone::findOrFail($id);

        $this->authorize('update', $milestone);

        $milestone->fill($validatedData);

        $milestone->save();

        return redirect('/milestone/show/'.$milestone->id)
            ->with('success', 'Milestone "'.$milestone->name.'" updated successfully!');
    }

    public function edit($id)
    {
        $milestone = Milestone::findOrFail($id);

        $this->authorize('update', $milestone);

        $users = User::pluck('name', 'id');

        return view('milestone.edit', compact('milestone', 'users'));
    }

    public function store(StoreMilestoneRequest $request)
    {
        $validated = $request->validated();

        if ($request->id == 'new') {
            $validated['active'] = 1;

            Milestone::create($validated);

        } else {
            $milestone = Milestone::findOrFail($request->id);

            $this->authorize('update', $milestone);

            $milestone->update($validated);
        }

        return redirect('milestone');
    }

    public function toggleWatcher($id)
    {
        $milestone = Milestone::findOrFail($id);

        $this->authorize('watch', $milestone);

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

        $this->authorize('viewReport', $milestone);

        $tickets = $milestone->tickets()
            ->with([
                'status', 'type', 'importance', 'project', 'assignee',
                'notes' => function ($q) {
                    $q->where('hide', 0);
                },
                'notes.user',
            ])
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

            $lastClosed = 0;
            for ($i = 0; $i <= $daysInSprint; $i++) {
                $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                if (isset($runningDates[$date])) {
                    $lastClosed = $runningDates[$date];
                }
                $actualBurndown[] = max(0, $totalStoryPoints - $lastClosed);
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
