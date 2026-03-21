<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('name')->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $perpage = 10;

        $filters = ['milestone_id', 'status_id', 'type_id', 'user_id', 'importance_id'];

        $queryfilter = [];

        foreach ($filters as $filter) {
            if (isset($request->$filter) && is_numeric($request->$filter)) {
                $queryfilter[$filter] = $request->$filter;
            }
        }

        $query = Ticket::query()->where('project_id', $project->id);

        if (is_array($queryfilter) && count($queryfilter) > 0) {
            foreach ($queryfilter as $filter => $value) {
                $query = $query->where($filter, $value);
            }
        }

        $tickets = $query
            ->with(['status', 'type', 'importance', 'project', 'assignee', 'notes' => function ($q) {
                $q->where('hide', 0)->where('notetype', 'message');
            }])
            ->paginate($perpage);

        $statuscodes = Status::get();

        $percent = 0;

        $completed = $project->tickets()->whereIn('status_id', Status::closedStatusIds())->count();

        $total = $project->tickets->count();

        if ($total !== 0 && $completed !== 0) {
            $percent = round($completed / $total, 2) * 100;
        }

        return view('projects.show', compact('project', 'tickets', 'queryfilter', 'total', 'completed', 'percent', 'statuscodes'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function store(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        if ($request->id == 'new') {
            $this->authorize('create', Project::class);

            $validated['active'] = 1;

            Project::create($validated);
        } else {
            $project = Project::findOrFail($request->id);
            $this->authorize('update', $project);

            $project->update($validated);
        }

        return redirect('projects');
    }
}
