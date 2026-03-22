<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function lookups()
    {
        return response()->json([
            'data' => [
                'statuses' => Status::orderBy('name')->get(['id', 'name']),
                'types' => Type::orderBy('name')->get(['id', 'name']),
                'importance' => Importance::orderBy('name')->get(['id', 'name']),
                'projects' => Project::where('active', 1)->orderBy('name')->get(['id', 'name']),
                'milestones' => Milestone::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->attributes->get('api_user');
        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = Ticket::with(['status', 'importance']);

        if ($request->boolean('unassigned')) {
            $query->where(function ($q) {
                $q->whereNull('user_id2')->orWhere('user_id2', 0);
            });
        } else {
            $query->where('user_id2', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'DESC')->paginate($perPage);

        $data = $tickets->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'estimate' => $ticket->estimate,
                'status' => $ticket->status->name ?? null,
                'importance' => $ticket->importance->name ?? null,
                'due_at' => $ticket->due_at,
                'closed_at' => $ticket->closed_at,
                'created_at' => $ticket->created_at->toDateString(),
                'link' => "/api/v1/tickets/{$ticket->id}",
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('api_user');

        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'nullable|integer|exists:types,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
        ]);

        $ticket = Ticket::create([
            'subject' => $request->subject,
            'description' => $request->description ?? '',
            'type_id' => $request->type_id ?? 1,
            'importance_id' => $request->importance_id ?? 1,
            'project_id' => $request->project_id ?? 1,
            'milestone_id' => $request->milestone_id ?? 1,
            'due_at' => $request->due_at ?? null,
            'estimate' => $request->estimate ?? 0,
            'storypoints' => $request->storypoints ?? 0,
            'user_id' => $user->id,
            'user_id2' => $user->id,
            'status_id' => 1,
        ]);

        return response()->json([
            'message' => 'Ticket created successfully',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => 'new',
                'link' => "/api/v1/tickets/{$ticket->id}",
            ],
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->attributes->get('api_user');

        $ticket = Ticket::with(['status', 'type', 'importance', 'milestone', 'project', 'assignee', 'notes' => function ($q) {
            $q->where('hide', 0)->with('user');
        }])
            ->where('user_id2', $user->id)
            ->findOrFail($id);

        $notes = $ticket->notes->map(function ($note) {
            return [
                'id' => $note->id,
                'user' => $note->user->name ?? null,
                'body' => $note->body,
                'hours' => $note->hours,
                'created_at' => $note->created_at->toDateTimeString(),
            ];
        });

        return response()->json(['data' => [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'estimate' => $ticket->estimate,
            'storypoints' => $ticket->storypoints,
            'status' => $ticket->status->name ?? null,
            'type' => $ticket->type->name ?? null,
            'importance' => $ticket->importance->name ?? null,
            'milestone' => $ticket->milestone->name ?? null,
            'project' => $ticket->project->name ?? null,
            'assignee' => $ticket->assignee->name ?? null,
            'due_at' => $ticket->due_at,
            'closed_at' => $ticket->closed_at,
            'created_at' => $ticket->created_at->toDateString(),
            'notes' => $notes,
        ]]);
    }

    public function note(Request $request, $id)
    {
        $user = $request->attributes->get('api_user');

        $ticket = Ticket::where('user_id2', $user->id)->findOrFail($id);

        if ($request->boolean('claim')) {
            $ticket->user_id2 = $user->id;
            $ticket->save();
        }

        if ($request->has('status_id') && $request->status_id != $ticket->status_id) {
            $ticket->status_id = $request->status_id;

            if (Status::isClosed($request->status_id)) {
                $ticket->closed_at = now();
            } else {
                $ticket->closed_at = null;
            }

            $ticket->save();
        }

        if ($request->has('body') || $request->has('hours')) {
            Note::create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'body' => $request->body ?? '',
                'hours' => $request->hours ?? 0,
                'notetype' => 'message',
            ]);
        }

        $ticket->load(['status', 'assignee']);

        return response()->json([
            'message' => 'Note added successfully',
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
            ],
        ]);
    }
}
