<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['status', 'importance'])
            ->where('user_id2', $request->user()->id);

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

    public function show(Request $request, $id)
    {
        $ticket = Ticket::with(['status', 'type', 'importance', 'milestone', 'project', 'assignee'])
            ->where('user_id2', $request->user()->id)
            ->findOrFail($id);

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
        ]]);
    }
}
