<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Services\PriorityMatrixService;
use App\Services\TicketQueryParser;
use App\Services\TicketService;
use Illuminate\Http\Request;

class ProjectsController extends Controller
{
    public function __construct(
        private PriorityMatrixService $priorityMatrix,
        private TicketService $ticketService,
        private TicketQueryParser $ticketQueryParser,
    ) {
    }

    public function index()
    {
        $projects = Project::withCount([
            'tickets as active_tickets_count' => function ($q) {
                $q->whereIn('status_id', Status::activeStatusIds());
            },
            'tickets as total_tickets_count',
        ])->orderBy('name')->paginate(25);

        return view('projects.index', compact('projects'));
    }

    public function show(Request $request, $id)
    {
        $project = Project::with(['tickets' => function ($q) {
            $q->with(['notes' => function ($noteQ) {
                $noteQ->where('hide', 0)->where('notetype', 'message');
            }]);
        }])->findOrFail($id);

        $this->authorize('view', $project);

        $perpage = $request->filled('perpage') ? min(max((int) $request->perpage, 1), 100) : 10;

        $searchQuery = (string) $request->input('q', '');
        $searchTokens = $this->ticketQueryParser->tokenize($searchQuery);

        $tickets = Ticket::query()
            ->where('project_id', $project->id)
            ->filter($this->ticketQueryParser->parse($searchQuery))
            ->with(['status', 'type', 'importance', 'project', 'assignee', 'notes' => function ($q) {
                $q->where('hide', 0)->where('notetype', 'message');
            }])
            ->sortable(
                ['subject', 'importance_id', 'status_id', 'project_id', 'created_at', 'updated_at'],
                ['importance_id', 'desc']
            )
            ->paginate($perpage)
            ->withQueryString();

        $statuscodes = Status::get();

        $percent = 0;

        $total = $project->tickets()->count();
        $completed = $project->tickets()->whereIn('status_id', Status::closedStatusIds())->count();

        if ($total !== 0) {
            $percent = round($completed / $total, 2) * 100;
        }

        $openTickets = Ticket::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status_id', Status::closedStatusIds())
            ->with('importance')
            ->get();

        $matrix = $this->priorityMatrix->classify($openTickets);

        $blockers = Ticket::where('project_id', $project->id)
            ->blockers()
            ->with(['importance', 'status'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('projects.show', compact('project', 'tickets', 'total', 'completed', 'percent', 'statuscodes', 'matrix', 'searchQuery', 'searchTokens', 'blockers'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

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

        if ($request->id === 'new') {
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
