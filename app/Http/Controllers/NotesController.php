<?php

namespace App\Http\Controllers;

use App\Exceptions\SlashCommandException;
use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\NoteReaction;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\MarkdownService;
use App\Services\MentionService;
use App\Services\NotePromotionService;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotesController extends Controller
{
    public function hide($id)
    {

        $note = Note::with('ticket')->findOrFail($id);

        $this->authorize('hide', $note);

        $note->hide = true;
        $note->save();

        return response()->json(['message' => 'Note Removed!']);
    }

    public function promote(Request $request, $id, NotePromotionService $promotion)
    {
        $note = Note::with('ticket')->findOrFail($id);

        $this->authorize('promote', $note);

        $validated = $request->validate([
            'type' => 'required|in:decision,blocker,action,decision_needed',
            'assignee' => 'nullable|string',
        ]);

        $error = $promotion->promote($note, $validated['type'], $validated['assignee'] ?? null);

        if ($error) {
            throw ValidationException::withMessages(['type' => $error])->redirectTo(url('tickets/'.$note->ticket_id));
        }

        return redirect('tickets/'.$note->ticket_id);
    }

    public function reply(Request $request, TicketService $ticketService)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'parent_id' => 'required|exists:notes,id',
            'body' => 'required|string|min:1',
        ]);

        $parent = Note::findOrFail($validated['parent_id']);

        if ((int) $parent->ticket_id !== (int) $validated['ticket_id']) {
            if (! ($request->expectsJson() || $request->ajax())) {
                return redirect("/tickets/{$validated['ticket_id']}#note_{$validated['parent_id']}")
                    ->withErrors(['parent_id' => 'Parent note does not belong to this ticket.'])
                    ->withInput();
            }

            return response()->json(['errors' => ['parent_id' => ['Parent note does not belong to this ticket.']]], 422);
        }

        if ($parent->parent_id !== null) {
            if (! ($request->expectsJson() || $request->ajax())) {
                return redirect("/tickets/{$validated['ticket_id']}#note_{$parent->id}")
                    ->withErrors(['parent_id' => 'Cannot reply to a reply. Only top-level notes accept replies.'])
                    ->withInput();
            }

            return response()->json(['errors' => ['parent_id' => ['Cannot reply to a reply. Only top-level notes accept replies.']]], 422);
        }

        try {
            $ticketService->notate(
                (int) $validated['ticket_id'],
                $validated['body'],
                [],
                0,
                (int) $parent->id
            );
        } catch (SlashCommandException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['errors' => ['body' => [$e->getMessage()]]], 422);
            }

            return redirect("/tickets/{$validated['ticket_id']}#note_{$parent->id}")
                ->withErrors(['body' => $e->getMessage()])
                ->withInput();
        }

        $note = Note::query()
            ->where('ticket_id', $validated['ticket_id'])
            ->where('parent_id', $parent->id)
            ->where('user_id', Auth::id())
            ->latest('id')
            ->firstOrFail();

        $note->load('user');
        $parent->load('replies.user');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'id' => $note->id,
                'ticket_id' => $note->ticket_id,
                'parent_id' => $note->parent_id,
                'body' => $note->body,
                'body_markdown' => $note->body_markdown,
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ],
                'replies_html' => view('partials.replies-section', ['note' => $parent])->render(),
            ]);
        }

        return redirect("/tickets/{$note->ticket_id}#note_{$parent->id}");
    }

    public function update($id, Request $request, MarkdownService $markdown, MentionService $mentions)
    {
        $note = Note::findOrFail($id);

        $this->authorize('update', $note);

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

        $usernames = $mentions->parseMentions($validated['body']);
        $userIds = User::whereIn('name', $usernames)->pluck('id')->all();
        $mentions->createMentions($note, $userIds);

        return response()->json([
            'body' => $note->body,
            'body_markdown' => $note->body_markdown,
            'edited_at' => $note->edited_at,
        ]);
    }

    public function attach($id, Request $request, AttachmentService $attachmentService)
    {
        $note = Note::with('ticket')->findOrFail($id);

        $this->authorize('attach', $note);

        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,zip,txt,log,csv,md',
        ]);

        $data = $attachmentService->store($validated['file'], "attachments/{$note->ticket_id}");

        $attachment = NoteAttachment::create([
            'note_id' => $note->id,
            'user_id' => Auth::id(),
            'ticket_id' => $note->ticket_id,
            ...$data,
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
        $note = Note::with('ticket')->findOrFail($id);

        $this->authorize('pin', $note);

        $note->update(['pinned' => ! $note->pinned]);

        return response()->json(['pinned' => $note->pinned]);
    }

    public function resolve($id, Request $request)
    {
        $note = Note::with('ticket')->findOrFail($id);

        $this->authorize('resolve', $note);

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

        DB::transaction(function () use ($note, $validated) {
            $reaction = NoteReaction::query()
                ->where('note_id', $note->id)
                ->where('user_id', Auth::id())
                ->where('emoji', $validated['emoji'])
                ->lockForUpdate()
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
        });

        $note->load('reactions');

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'note_id' => $note->id,
                'reactions' => $note->groupedReactions(),
                'html' => view('partials.reaction-bar', ['note' => $note])->render(),
            ]);
        }

        return redirect("/tickets/{$note->ticket_id}#note_{$note->id}");
    }
}
