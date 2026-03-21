<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReleaseRequest;
use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Type;
use Illuminate\Support\Facades\Auth;

class ReleaseController extends Controller
{
    public function index()
    {

        $releases = Release::all();

        return View('release/index', compact('releases'));

    }

    public function create()
    {
        return View('release/create');
    }

    public function edit($id)
    {
        $release = Release::findOrFail($id);
        $this->authorize('update', $release);

        return View('release/edit', compact('release'));
    }

    public function put(StoreReleaseRequest $request, $id)
    {
        $release = Release::findOrFail($id);
        $this->authorize('update', $release);

        $validated = $request->validated();

        $release->title = $validated['title'];
        $release->body = $validated['body'] ?? null;

        if (! empty($validated['started_at'])) {
            $release->started_at = $validated['started_at'];
        } else {
            $release->started_at = null;
        }

        if (! empty($validated['completed_at'])) {
            $release->completed_at = $validated['completed_at'];
        } else {
            $release->completed_at = null;
        }

        $release->save();

        return redirect('release/'.$release->id)->with('info_message', 'Release Saved');
    }

    public function show($id)
    {
        $release = Release::with('owner')->findOrFail($id);

        $release_tickets = ReleaseTicket::with([
            'ticket' => function ($q) {
                $q->with(['project', 'type', 'status', 'assignee']);
            },
        ])->where('release_id', $release->id)->get();

        $tickets = [];
        $projects = [];

        foreach ($release_tickets as $ticket) {

            if (! array_key_exists($ticket->ticket->project_id, $projects)) {

                $projects[$ticket->ticket->project_id]['project'] = $ticket->ticket->project->name;
            }

            $projects[$ticket->ticket->project_id]['tickets'][$ticket->ticket->type->name][] = $ticket->ticket;

        }

        $types = Type::pluck('icon', 'name');

        return View('release/show', compact('release', 'projects', 'types', 'release_tickets'));
    }

    public function store(StoreReleaseRequest $request)
    {
        $this->authorize('create', Release::class);

        $validated = $request->validated();

        $release = Release::create([
            'title' => $validated['title'],
            'started_at' => $validated['started_at'] ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
            'body' => $validated['body'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return redirect('release/'.$release->id)->with('info_message', 'Release Saved');

    }
}
