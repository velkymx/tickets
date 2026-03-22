<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchUpdateTicketRequest;
use App\Http\Requests\EstimateTicketRequest;
use App\Http\Requests\NoteTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\ReleaseTicket;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\TicketUserWatcher;
use App\Models\TicketView;
use App\Services\TicketPulseService;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TicketsController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    public function home()
    {
        $user = Auth::user();

        $openStatusIds = Status::whereNotIn('id', Status::closedStatusIds())->pluck('id')->toArray();
        $closedStatusIds = Status::closedStatusIds();

        $tickets = Ticket::where('user_id2', $user->id)
            ->whereIn('status_id', $openStatusIds)
            ->with(['status', 'type', 'importance', 'project', 'assignee', 'notes' => function ($q) {
                $q->where('hide', 0)->where('notetype', 'message');
            }])
            ->get()
            ->groupBy('status_id');

        $alltickets = [];
        foreach ($tickets as $statusId => $ticketGroup) {
            $statusName = $ticketGroup->first()->status->name ?? null;
            if ($statusName) {
                $alltickets[$statusName] = $ticketGroup;
            }
        }

        $stats = [
            'assigned' => Ticket::where('user_id2', $user->id)->count(),
            'open' => Ticket::where('user_id2', $user->id)->whereIn('status_id', $openStatusIds)->count(),
            'closed' => Ticket::where('user_id2', $user->id)->whereIn('status_id', $closedStatusIds)->count(),
            'watching' => Ticket::whereHas('watchers', fn ($q) => $q->where('user_id', $user->id))->count(),
        ];

        $recentTickets = Ticket::where('user_id2', $user->id)
            ->orWhere('user_id', $user->id)
            ->with(['status', 'type', 'importance', 'project', 'assignee'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentNotes = $user->notes()
            ->with(['ticket.status', 'ticket.type', 'ticket.importance'])
            ->whereHas('ticket')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->filter(fn ($note) => $note->ticket !== null);

        return View('home', compact('alltickets', 'stats', 'recentTickets', 'recentNotes'));
    }

    public function index(Request $request)
    {
        $perpage = 10;

        if ($request->has('perpage')) {
            $perpage = (int) $request->perpage;
        }

        $filters = ['milestone_id', 'project_id', 'status_id', 'type_id', 'user_id', 'importance_id', 'q'];

        $query = Ticket::query();

        $queryfilter = [];

        foreach ($filters as $filter) {

            $queryfilter[$filter] = $request->$filter;

            if ($request->has($filter) && is_numeric($request->$filter)) {

                $query = $query->where($filter, $request->$filter);
            }

            if ($filter == 'q' && $request->filled('q')) {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->$filter);
                $query = $query->where('subject', 'like', '%'.$search.'%');
            }

            if ($filter == 'status_id' && $request->status_id == 'none') {

                $query = $query->whereNotIn('status_id', Status::closedStatusIds());

            }
        }

        $tickets = $query
            ->with(['status', 'type', 'importance', 'project', 'assignee', 'notes' => function ($q) {
                $q->where('hide', 0)->where('notetype', 'message');
            }])
            ->orderBy('importance_id', 'DESC')
            ->paginate($perpage);

        $lookups = $this->ticketService->getLookups();

        $lookups['types'][0] = 'No Change';
        $lookups['milestones'][0] = 'No Change';
        $lookups['importances'][0] = 'No Change';
        $lookups['projects'][0] = 'No Change';
        $lookups['statuses'][0] = 'No Change';
        $lookups['users'][0] = 'No Change';
        $lookups['releases'][0] = 'No Change';

        $viewfilters = $this->ticketService->getLookups();

        $viewfilters['statuses']['none'] = 'Any Active Status';
        $viewfilters['statuses']['all'] = 'Any Status';
        $viewfilters['types']['none'] = 'Any Type';
        $viewfilters['milestones']['none'] = 'Any Milestone';

        $filter = [
            'milestone_id' => 'none',
            'type_id' => 'none',
            'status_id' => 'none',
        ];

        foreach ($filter as $fk => $fv) {

            if ($request->has($fk)) {

                $filter[$fk] = $request->$fk;
            }

        }

        return view('tickets.list', compact('tickets', 'queryfilter', 'lookups', 'viewfilters', 'filter'));
    }

    public function claim($id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('claim', $ticket);

        $request = $ticket->toArray();

        $request['user_id2'] = Auth::id();

        $change_list = $this->ticketService->changes($ticket->toArray(), $request);

        $ticket->update($request);

        $this->ticketService->notate($ticket->id, '', $change_list);

        return redirect('tickets/'.$id)->with('info_message', 'You are assigned to Ticket #'.$id);
    }

    public function show($id)
    {
        $ticket = Ticket::with([
            'status', 'type', 'importance', 'project', 'assignee', 'user',
            'watchers.user',
            'estimates.user',
            'notes' => function ($q) {
                $q->where('hide', 0)->orderBy('created_at', 'desc');
            },
            'notes.user',
        ])->findOrFail($id);

        $this->authorize('view', $ticket);

        $lookups = $this->ticketService->getLookups();

        TicketView::firstOrCreate([
            'user_id' => Auth::id(),
            'ticket_id' => $ticket->id,
        ]);

        $ticketViews = TicketView::select('user_id', \DB::raw('max(created_at) as viewed_at'))
            ->where('ticket_id', $ticket->id)
            ->groupBy('user_id')
            ->with('user')
            ->get();

        $pulse = app(TicketPulseService::class)->getPulse($ticket);

        return view('tickets.show', compact('ticket', 'lookups', 'ticketViews', 'pulse'));
    }

    public function create($value = '')
    {
        $lookups = $this->ticketService->getLookups();

        return view('tickets.create', compact('lookups'));
    }

    public function clone($id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('update', $ticket);

        $lookups = $this->ticketService->getLookups();

        if (! empty($ticket->closed_at)) {
            $ticket->closed_at = Carbon::parse($ticket->closed_at)->format('m/d/Y');
        }

        if (! empty($ticket->due_at)) {
            $ticket->due_at = Carbon::parse($ticket->due_at)->format('m/d/Y');
        }

        return view('tickets.clone', compact('ticket', 'lookups'));
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('update', $ticket);

        $lookups = $this->ticketService->getLookups();

        if (! empty($ticket->closed_at)) {
            $ticket->closed_at = Carbon::parse($ticket->closed_at)->format('m/d/Y');
        }

        if (! empty($ticket->due_at)) {
            $ticket->due_at = Carbon::parse($ticket->due_at)->format('m/d/Y');
        }

        return view('tickets.edit', compact('ticket', 'lookups'));
    }

    public function update(UpdateTicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('update', $ticket);

        $data = $request->validated();

        if (! empty($data['due_at'])) {
            $data['due_at'] = Carbon::parse($data['due_at'])->format('Y-m-d');
        } else {
            $data['due_at'] = null;
        }

        if (! empty($data['closed_at'])) {
            $data['closed_at'] = Carbon::parse($data['closed_at'])->format('Y-m-d H:i:s');
        } else {
            $data['closed_at'] = null;
        }

        $change_list = $this->ticketService->changes($ticket->toArray(), $data);

        $ticket->update($data);

        $this->ticketService->notate($ticket->id, '', $change_list);

        return redirect('tickets/'.$id)->with('info_message', 'Ticket #'.$id.' updated');
    }

    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();

        $data['user_id'] = Auth::id();
        $data['user_id2'] = Auth::id();

        if (! empty($data['due_at'])) {
            $data['due_at'] = Carbon::parse($data['due_at'])->format('Y-m-d');
        }

        $insert = Ticket::create($data);

        return redirect('tickets/'.$insert->id)->with('status', 'Task was created successfully!');
    }

    public function upload(UploadTicketRequest $request)
    {
        $file = $request->file('file');
        $folder = preg_replace('/[^a-zA-Z0-9_-]/', '', $request->input('folder'));

        $filename = time().'_'.Str::random(10).'.'.$file->getClientOriginalExtension();
        $path = 'images/'.$folder.'/'.$filename;

        $file->move(public_path('images/'.$folder), $filename);

        return '/'.$path;
    }

    public function batch(BatchUpdateTicketRequest $request)
    {
        $validated = $request->validated();

        $ticketIds = array_keys($validated['tickets']);

        $tickets = Ticket::whereIn('id', $ticketIds)->get()->keyBy('id');

        $updateFields = array_filter([
            'type_id' => $validated['type_id'] ?? null,
            'status_id' => $validated['status_id'] ?? null,
            'importance_id' => $validated['importance_id'] ?? null,
            'milestone_id' => $validated['milestone_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'user_id2' => $validated['user_id2'] ?? null,
        ], fn ($value) => $value !== null && $value !== 0);

        $i = 0;

        DB::transaction(function () use ($ticketIds, $tickets, $updateFields, $validated, &$i) {
            foreach ($ticketIds as $ticketId) {
                if (! $tickets->has($ticketId)) {
                    continue;
                }

                $ticket = $tickets->get($ticketId);

                Gate::authorize('update', $ticket);

                if (! empty($validated['release_id']) && $validated['release_id'] > 0) {
                    ReleaseTicket::firstOrCreate([
                        'release_id' => $validated['release_id'],
                        'ticket_id' => $ticketId,
                    ]);
                }

                if (! empty($updateFields)) {
                    $ticket->update($updateFields);
                }

                $i++;
            }
        });

        return redirect('tickets')->with('info_message', $i.' ticket(s) updated');
    }

    public function board()
    {
        $perpage = 50;
        $tickets = Ticket::with(['status', 'type', 'importance', 'project', 'assignee'])
            ->paginate($perpage);

        $lookups = $this->ticketService->getLookups();

        return view('tickets.board', compact('tickets', 'lookups'));
    }

    public function api(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'required|integer|exists:statuses,id',
        ]);

        if ($request['status'] != $ticket->status_id) {
            $ticket->update(['status_id' => $request['status']]);

            $this->ticketService->notate($ticket->id, '', ['Status Changed to '.$ticket->status->name]);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['error' => 'Status unchanged'], 400);
    }

    public function note(NoteTicketRequest $request)
    {
        $validated = $request->validated();

        if ($request->has('status_id') && $request->has('ticket_id')) {

            $ticket = Ticket::withSum('notes', 'hours')->findOrFail($request->ticket_id);

            $this->authorize('update', $ticket);

            $old = $ticket->toArray();

            if ($ticket->status_id != $request->status_id) {

                if (Status::isClosed($request->status_id)) {
                    $ticket->closed_at = now();
                } else {
                    $ticket->closed_at = null;
                }

                $ticket->status_id = $request->status_id;
                $ticket->save();
            }

            $change_list = $this->ticketService->changes($old, $ticket->toArray());

            $this->ticketService->notate($ticket->id, $validated['note'] ?? '', $change_list, $validated['hours'] ?? 0);

            $ticket->unsetRelation('notes');
            $ticket->loadSum('notes', 'hours');
            $ticket->actual = $ticket->notes_sum_hours ?? 0;
            $ticket->save();
        }

        return redirect('tickets/'.$request['ticket_id']);
    }

    public function estimate(EstimateTicketRequest $request, $ticket_id)
    {
        $validated = $request->validated();

        $ticket = Ticket::findOrFail($ticket_id);
        $this->authorize('estimate', $ticket);

        $check = TicketEstimate::updateOrCreate(
            ['ticket_id' => $ticket_id, 'user_id' => Auth::id()],
            ['storypoints' => $validated['storypoints']]
        );

        $getAvg = TicketEstimate::where('ticket_id', $ticket_id)->get();

        $total = $getAvg->sum('storypoints');

        $fibs = [0, 1, 2, 3, 5, 8, 13, 21];

        if ($getAvg->count() === 0) {
            return redirect('tickets/'.$ticket_id);
        }

        $avg = $total / $getAvg->count();

        $sp = end($fibs);
        foreach ($fibs as $fib) {
            if ($avg <= $fib) {
                $sp = $fib;
                break;
            }
        }

        $ticket = Ticket::find($ticket_id);
        $old = clone $ticket;

        $ticket->storypoints = $sp;

        $ticket->save();

        $change_list = $this->ticketService->changes($old->toArray(), $ticket->toArray());

        $this->ticketService->notate($ticket->id, '', ['Story Points changed to '.$request->storypoints]);

        return redirect('tickets/'.$ticket_id);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'started_at' => 'required|date',
            'completed_at' => 'required|date|after_or_equal:started_at',
        ]);

        return TicketResource::collection(
            Ticket::where('user_id2', Auth::id())
                ->whereBetween('closed_at', [$request->started_at, $request->completed_at])
                ->get()
        );
    }

    public function toggleWatcher($id)
    {
        $ticket = Ticket::findOrFail($id);
        $watcher = TicketUserWatcher::where('ticket_id', $id)->where('user_id', Auth::id())->first();

        if ($watcher) {
            $watcher->delete();
        } else {
            TicketUserWatcher::create([
                'ticket_id' => $id,
                'user_id' => Auth::id(),
            ]);
        }

        return redirect()->back();
    }
}
