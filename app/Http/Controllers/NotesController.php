<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Services\MarkdownService;
use App\Services\MentionService;
use Illuminate\Http\Request;
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

    public function reply(Request $request, MarkdownService $markdown, MentionService $mentions)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'parent_id' => 'required|exists:notes,id',
            'body' => 'required|string|min:1',
        ]);

        $parent = Note::findOrFail($validated['parent_id']);

        if ((int) $parent->ticket_id !== (int) $validated['ticket_id']) {
            return response()->json(['errors' => ['parent_id' => ['Parent note does not belong to this ticket.']]], 422);
        }

        if ($parent->parent_id !== null) {
            return response()->json(['errors' => ['parent_id' => ['Cannot reply to a reply. Only top-level notes accept replies.']]], 422);
        }

        $html = $markdown->parse($validated['body']);

        $note = Note::create([
            'ticket_id' => $validated['ticket_id'],
            'parent_id' => $parent->id,
            'user_id' => Auth::id(),
            'body' => $html,
            'body_markdown' => $validated['body'],
            'notetype' => 'message',
            'hours' => 0,
            'hide' => false,
        ]);

        $mentions->createMentions($note, $mentions->parseMentions($validated['body']));

        $note->load('user');

        return response()->json(['note' => $note], 201);
    }

    public function update($id, Request $request, MarkdownService $markdown, MentionService $mentions)
    {
        $note = Note::findOrFail($id);

        if ((int) $note->user_id !== (int) Auth::id()) {
            abort(Response::HTTP_FORBIDDEN, 'Only the author can edit this note.');
        }

        if ($note->notetype === 'decision') {
            return response()->json([
                'message' => 'Decisions cannot be edited. Use /decision to create a new superseding decision.',
            ], 422);
        }

        $validated = $request->validate([
            'body' => 'required|string|min:1',
        ]);

        $html = $markdown->parse($validated['body']);

        $note->update([
            'body' => $html,
            'body_markdown' => $validated['body'],
            'edited_at' => now(),
        ]);

        $mentions->createMentions($note, $mentions->parseMentions($validated['body']));

        return response()->json([
            'body' => $note->body,
            'body_markdown' => $note->body_markdown,
            'edited_at' => $note->edited_at,
        ]);
    }

    public function attach($id, Request $request)
    {
        $note = Note::findOrFail($id);

        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,zip,txt,log,csv,md',
        ]);

        $file = $validated['file'];
        $path = $file->store("attachments/{$note->ticket_id}", 'public');

        $attachment = \App\Models\NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => Auth::id(),
            'ticket_id' => $note->ticket_id,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'url' => $attachment->url,
            'mime_type' => $attachment->mime_type,
            'isImage' => $attachment->isImage,
        ], 201);
    }

    public function togglePin($id)
    {
        $note = Note::findOrFail($id);
        $note->update(['pinned' => ! $note->pinned]);

        return response()->json(['pinned' => $note->pinned]);
    }

    public function resolve($id, Request $request)
    {
        $note = Note::with('ticket')->findOrFail($id);

        $canResolve = (int) $note->user_id === (int) Auth::id()
            || (int) $note->ticket->user_id2 === (int) Auth::id();

        if (! $canResolve) {
            abort(Response::HTTP_FORBIDDEN, 'Only the thread author or ticket assignee can resolve.');
        }

        $validated = $request->validate([
            'resolution_message' => 'required|string|min:1',
        ]);

        $note->update([
            'resolved' => true,
            'resolved_by' => Auth::id(),
            'resolution_message' => $validated['resolution_message'],
        ]);

        return response()->json(['resolved' => true]);
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
