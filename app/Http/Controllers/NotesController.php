<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteReaction;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotesController extends Controller
{
    public function hide($id)
    {

        $note = Note::with('ticket')->findOrFail($id);

        $canHide = $note->user_id === Auth::id()
            || $note->ticket->user_id === Auth::id()
            || $note->ticket->user_id2 === Auth::id();

        if (! $canHide) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot hide this note');
        }

        $note->hide = true;
        $note->save();

        return response()->json(['message' => 'Note Removed!']);
    }

    public function promote($id)
    {
        $note = Note::with('ticket')->findOrFail($id);

        $canPromote = $note->user_id === Auth::id()
            || $note->ticket->user_id === Auth::id()
            || $note->ticket->user_id2 === Auth::id();

        if (! $canPromote) {
            abort(Response::HTTP_FORBIDDEN, 'You cannot promote this note');
        }

        $validator = Validator::make(request()->all(), [
            'type' => 'required|in:decision,blocker,action',
            'assignee' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($note) {
            $type = request('type');
            $body = strip_tags($note->body);

            if ($note->notetype !== 'message') {
                $validator->errors()->add('type', 'Only message notes can be promoted.');
            }

            if ($type === 'decision' && mb_strlen(trim($body)) < 20) {
                $validator->errors()->add('type', 'Decision notes must be at least 20 characters.');
            }

            if ($type === 'action' && ! preg_match('/@([\w.\-]+)/', $body) && ! request('assignee')) {
                $validator->errors()->add('type', 'Actions require exactly one @assignee');
            }
        });

        if ($validator->fails()) {
            return redirect('tickets/'.$note->ticket_id)->withErrors($validator)->withInput();
        }

        if (request('type') === 'action' && request('assignee') && ! preg_match('/@([\w.\-]+)/', $note->body)) {
            $note->body = rtrim($note->body).' @'.ltrim((string) request('assignee'), '@');
        }

        $note->notetype = request('type');
        $note->save();

        return redirect('tickets/'.$note->ticket_id);
    }

    public function toggleReaction($id)
    {
        $validated = request()->validate([
            'emoji' => 'required|in:'.implode(',', NoteReaction::ALLOWED_EMOJIS),
        ]);

        $note = Note::findOrFail($id);

        $reaction = NoteReaction::query()
            ->where('note_id', $note->id)
            ->where('user_id', Auth::id())
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($reaction) {
            $reaction->delete();
        } else {
            NoteReaction::create([
                'note_id' => $note->id,
                'user_id' => Auth::id(),
                'emoji' => $validated['emoji'],
            ]);
        }

        $note->load('reactions');

        return response()->json([
            'reactions' => $note->groupedReactions(),
        ]);
    }
}
