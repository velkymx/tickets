<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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
}
