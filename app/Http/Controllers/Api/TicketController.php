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
        $query = Ticket::with(['status', 'importance']);

        if ($request->boolean('unassigned')) {
            $query->whereNull('user_id2')->orWhere('user_id2', 0);
        } else {
            $query->where('user_id2', $request->user()->id);
        }

        if ($request->has('status')) {
            $query->where('status_id', $request->status);
        }

        $tickets = $query->get();

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

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
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
            'user_id' => $request->user()->id,
            'user_id2' => $request->user()->id,
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
        $ticket = Ticket::with(['status', 'type', 'importance', 'milestone', 'project', 'assignee', 'notes.user'])
            ->where('user_id2', $request->user()->id)
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
        $ticket = Ticket::findOrFail($id);

        if ($request->boolean('claim')) {
            $ticket->user_id2 = $request->user()->id;
            $ticket->save();
        }

        if ($request->has('status_id') && $request->status_id != $ticket->status_id) {
            $ticket->status_id = $request->status_id;

            if ($request->status_id == 5) {
                $ticket->closed_at = now();
            }

            $ticket->save();
        }

        if ($request->has('body') || $request->has('hours')) {
            Note::create([
                'user_id' => $request->user()->id,
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
