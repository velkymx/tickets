<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\Type;
use App\Models\User;
use App\Services\MarkdownService;
use App\Services\MentionService;
use App\Services\SlashCommandService;
use App\Services\TicketPulseService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function lookups()
    {
        return response()->json([
            'data' => [
                'statuses' => Status::orderBy('id')->get(['id', 'name']),
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

        $includePulse = $request->get('include') === 'pulse';

        $eagerLoads = ['status', 'importance'];
        if ($includePulse) {
            $eagerLoads = array_merge($eagerLoads, ['notes.user', 'notes.supersedes', 'notes.replies', 'assignee']);
        }

        $query = Ticket::with($eagerLoads);

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
        $pulseService = $includePulse ? app(TicketPulseService::class) : null;

        $data = $tickets->map(function ($ticket) use ($includePulse, $pulseService) {
            $item = [
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

            if ($includePulse) {
                $pulse = $pulseService->getPulse($ticket);
                $item['pulse_summary'] = [
                    'execution_state' => $pulse->execution_state,
                    'is_blocked' => $pulse->is_blocked,
                    'has_open_actions' => ! empty($pulse->next_action['id']),
                    'has_decisions' => $pulse->latest_decision !== null,
                    'unresolved_thread_count' => count($pulse->open_threads),
                ];
            }

            return $item;
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
        $user = $request->attributes->get('api_user') ?? Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_id' => 'required|integer|exists:types,id',
            'importance_id' => 'required|integer|exists:importances,id',
            'project_id' => 'required|integer|exists:projects,id',
            'milestone_id' => 'required|integer|exists:milestones,id',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
        ]);

        // Present-but-null leaves the ticket unassigned; omitted defaults to self.
        $assigneeId = array_key_exists('assignee_id', $validated)
            ? $validated['assignee_id']
            : $user->id;

        $ticket = Ticket::create([
            'subject' => $request->subject,
            'description' => $request->description ?? '',
            'type_id' => $request->type_id,
            'importance_id' => $request->importance_id,
            'project_id' => $request->project_id,
            'milestone_id' => $request->milestone_id,
            'due_at' => $request->due_at ?? null,
            'estimate' => $request->estimate ?? 0,
            'storypoints' => $request->storypoints ?? 0,
            'user_id' => $user->id,
            'user_id2' => $assigneeId,
            'status_id' => $request->status_id ?? Status::orderBy('id')->first()?->id,
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

        $ticket = Ticket::with([
            'status', 'type', 'importance', 'milestone', 'project', 'assignee',
            'notes' => function ($q) use ($request) {
                $q->where('hide', 0)
                    ->whereNull('parent_id')
                    ->orderBy('created_at', 'asc')
                    ->with(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);

                if ($request->has('notetype')) {
                    $q->where('notetype', $request->notetype);
                }
            },
        ])->findOrFail($id);

        $apiUserId = $user->id;
        $notes = $ticket->notes->map(fn ($note) => $this->formatNote($note, $apiUserId));

        $pulse = app(TicketPulseService::class)->getPulse($ticket)->toArray();

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
            'pulse' => $pulse,
            'notes' => $notes,
        ]]);
    }

    protected function formatNote($note, int $apiUserId): array
    {
        $reactions = $note->reactions->groupBy('emoji')->map(fn ($group) => [
            'count' => $group->count(),
            'reacted' => $group->contains('user_id', $apiUserId),
        ])->toArray();

        return [
            'id' => $note->id,
            'user' => $note->user ? ['id' => $note->user->id, 'name' => $note->user->name] : null,
            'body' => $note->body,
            'body_markdown' => $note->body_markdown,
            'notetype' => $note->notetype ?? 'message',
            'hours' => $note->hours,
            'pinned' => (bool) $note->pinned,
            'edited_at' => $note->edited_at?->toDateTimeString(),
            'resolved' => (bool) $note->resolved,
            'resolved_by' => $note->resolved_by,
            'resolution_message' => $note->resolution_message,
            'parent_id' => $note->parent_id,
            'supersedes_id' => $note->supersedes_id,
            'created_at' => $note->created_at->toDateTimeString(),
            'reactions' => $reactions,
            'replies' => $note->replies->map(fn ($reply) => [
                'id' => $reply->id,
                'user' => $reply->user ? ['id' => $reply->user->id, 'name' => $reply->user->name] : null,
                'body' => $reply->body,
                'created_at' => $reply->created_at->toDateTimeString(),
            ])->values()->toArray(),
            'attachments' => $note->attachments->map(fn ($a) => [
                'id' => $a->id,
                'filename' => $a->filename,
                'url' => $a->url,
                'mime_type' => $a->mime_type,
                'is_image' => $a->is_image,
            ])->values()->toArray(),
            'mentions' => $note->mentions->map(fn ($m) => [
                'id' => $m->id,
                'user' => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
            ])->values()->toArray(),
        ];
    }

    public function note(Request $request, $id)
    {
        $request->validate([
            'status_id' => 'nullable|integer|exists:statuses,id',
            'hours' => 'nullable|numeric|min:0|max:999',
            'body' => 'nullable|string|max:65535',
        ]);

        $user = $request->attributes->get('api_user');

        $ticket = Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->findOrFail($id);

        // All writes run in one transaction so a failed slash-command guard
        // rolls back the claim/status changes too. Guard failures throw an
        // HttpResponseException, which unwinds the transaction and renders the
        // 422 directly.
        [$createdNote, $warnings] = DB::transaction(function () use ($request, $user, $ticket) {
            if ($request->boolean('claim')) {
                $ticket->user_id2 = $user->id;
                $ticket->save();
            }

            if ($request->has('status_id') && $request->status_id != $ticket->status_id) {
                $ticket->status_id = $request->status_id;
                $ticket->closed_at = Status::isClosed($request->status_id) ? now() : null;
                $ticket->save();
            }

            $createdNote = null;
            $warnings = [];

            if ($request->has('body') || $request->has('hours')) {
                $slashService = app(SlashCommandService::class);
                $markdownService = app(MarkdownService::class);
                $mentionService = app(MentionService::class);

                // Check for action constraint violations before running commands
                $bodyText = $request->body ?? '';
                if (preg_match('/^\/action\b/m', $bodyText)) {
                    $mentions = $this->extractMentionsFromText($bodyText);
                    if (count($mentions) !== 1) {
                        $this->rejectNote('Actions require exactly one @assignee');
                    }
                }

                $commandResult = $slashService->handle($ticket, $bodyText);
                $warnings = $commandResult['warnings'] ?? [];

                // Check for blocker/action constraints from the slash service
                $noteType = $commandResult['note_type'] ?? 'message';
                foreach ($commandResult['changes'] ?? [] as $change) {
                    if (str_contains($change, 'Resolve blocker before') || str_contains($change, 'Too many open actions')) {
                        $this->rejectNote($change);
                    }
                }

                $bodyText = $commandResult['body'] ?? '';
                $bodyHtml = $markdownService->parse($bodyText);
                $totalHours = ($request->hours ?? 0) + ($commandResult['hours'] ?? 0);

                // Skip commands that leave no text behind (e.g. /estimate, /close).
                if (trim(strip_tags($bodyHtml)) !== '' || $totalHours > 0) {
                    $createdNote = Note::create([
                        'user_id' => $user->id,
                        'ticket_id' => $ticket->id,
                        'body' => $bodyHtml,
                        'body_markdown' => $bodyText,
                        'hours' => $totalHours,
                        'notetype' => $noteType,
                        'pinned' => $commandResult['note_attributes']['pinned'] ?? false,
                    ]);

                    // Create mention records
                    $mentionUsernames = $mentionService->parseMentions($bodyText);
                    $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
                    $mentionService->createMentions($createdNote, $mentionUserIds);

                    $createdNote->load(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);
                }
            }

            return [$createdNote, $warnings];
        });

        $ticket->load(['status', 'assignee']);

        $response = [
            'message' => $createdNote ? 'Note added successfully' : 'Command applied, no note stored.',
            'warnings' => $warnings,
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
            ],
        ];

        if ($createdNote) {
            $response['note'] = $this->formatNote($createdNote, $user->id);
        }

        return response()->json($response);
    }

    /**
     * Abort a note write with a 422, unwinding the surrounding transaction so
     * any claim/status changes made alongside it are rolled back.
     */
    private function rejectNote(string $message): never
    {
        throw new HttpResponseException(response()->json(['message' => $message], 422));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'status_id' => 'sometimes|required|integer|exists:statuses,id',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'type_id' => 'nullable|integer|exists:types,id',
            'importance_id' => 'nullable|integer|exists:importances,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'milestone_id' => 'nullable|integer|exists:milestones,id',
            'due_at' => 'nullable|date',
            'estimate' => 'nullable|numeric|min:0',
            'storypoints' => 'nullable|integer|min:0',
        ]);

        $user = $request->attributes->get('api_user');

        $ticket = Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->findOrFail($id);

        if ($request->has('subject')) {
            $ticket->subject = $request->subject;
        }

        if ($request->has('description')) {
            $ticket->description = $request->description ?? '';
        }

        if ($request->has('status_id') && $request->status_id != $ticket->status_id) {
            $ticket->status_id = $request->status_id;
            $ticket->closed_at = Status::isClosed($request->status_id) ? now() : null;
        }

        if (! empty($validated['assignee_id'])) {
            $ticket->user_id2 = $validated['assignee_id'];
        }

        foreach (['type_id', 'importance_id', 'project_id', 'milestone_id'] as $fk) {
            if (! empty($validated[$fk])) {
                $ticket->{$fk} = $validated[$fk];
            }
        }

        // Numeric/date fields use array_key_exists so a caller can clear a due
        // date (null) or set a zero estimate, which an empty() check would drop.
        foreach (['due_at', 'estimate', 'storypoints'] as $field) {
            if (array_key_exists($field, $validated)) {
                $ticket->{$field} = $validated[$field];
            }
        }

        $ticket->save();
        $ticket->load(['status', 'assignee']);

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
            ],
        ]);
    }

    public function moderateNote(Request $request, $id, $noteId)
    {
        $validated = $request->validate([
            'action' => 'required|in:pin,unpin,hide,unhide',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        switch ($validated['action']) {
            case 'pin':
                $note->pinned = true;
                break;
            case 'unpin':
                $note->pinned = false;
                break;
            case 'hide':
                $note->hide = true;
                break;
            case 'unhide':
                $note->hide = false;
                break;
        }

        $note->save();

        return response()->json([
            'message' => 'Note updated successfully',
            'note' => [
                'id' => $note->id,
                'pinned' => (bool) $note->pinned,
                'hidden' => (bool) $note->hide,
            ],
        ]);
    }

    public function promoteNote(Request $request, $id, $noteId)
    {
        $validated = $request->validate([
            'type' => 'required|in:decision,blocker,action',
            'assignee' => 'nullable|string',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        if ($note->notetype !== 'message') {
            return response()->json(['message' => 'Only message notes can be promoted'], 422);
        }

        $type = $validated['type'];
        $body = strip_tags($note->body);
        $hasMention = (bool) preg_match('/@\[([^\]]+)\]/u', $note->body);

        if ($type === 'decision' && mb_strlen(trim($body)) < 20) {
            return response()->json(['message' => 'Decision notes must be at least 20 characters'], 422);
        }

        if ($type === 'action' && ! $hasMention && empty($validated['assignee'])) {
            return response()->json(['message' => 'Actions require exactly one @assignee'], 422);
        }

        // Append the assignee mention when supplied and not already present.
        if ($type === 'action' && ! empty($validated['assignee']) && ! $hasMention) {
            $assigneeName = ltrim((string) $validated['assignee'], '@');
            $note->body = rtrim($note->body).' @['.$assigneeName.']';
        }

        $note->notetype = $type;
        $note->save();

        return response()->json([
            'message' => 'Note promoted successfully',
            'note' => ['id' => $note->id, 'notetype' => $note->notetype],
        ]);
    }

    public function watch(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'nullable|in:watch,unwatch,mute,unmute',
        ]);

        // Any ticket is viewable, so watching is not limited to owned tickets.
        $ticket = Ticket::findOrFail($id);
        $user = $request->attributes->get('api_user');
        $action = $validated['action'] ?? 'watch';

        $watcher = TicketUserWatcher::where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->first();

        switch ($action) {
            case 'unwatch':
                $watcher?->delete();
                $watching = false;
                break;

            case 'mute':
            case 'unmute':
                $watcher ??= new TicketUserWatcher(['ticket_id' => $ticket->id, 'user_id' => $user->id]);
                $watcher->muted = $action === 'mute';
                $watcher->save();
                $watching = true;
                break;

            default:
                if (! $watcher) {
                    TicketUserWatcher::create([
                        'ticket_id' => $ticket->id, 'user_id' => $user->id, 'muted' => false,
                    ]);
                }
                $watching = true;
        }

        return response()->json([
            'message' => 'Watch state updated',
            'ticket_id' => $ticket->id,
            'watching' => $watching,
            'muted' => $action === 'mute',
        ]);
    }

    public function pulse(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $pulse = app(TicketPulseService::class)->getPulse($ticket)->toArray();

        return response()->json(['data' => $pulse]);
    }

    public function resolveNote(Request $request, $id, $noteId)
    {
        $request->validate([
            'resolution_message' => 'required|string|max:65535',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        // Only thread author or ticket assignee can resolve
        $isAuthor = (int) $note->user_id === (int) $user->id;
        $isAssignee = (int) $ticket->user_id2 === (int) $user->id;

        if (! $isAuthor && ! $isAssignee) {
            return response()->json(['message' => 'Forbidden: only the thread author or ticket assignee can resolve'], 403);
        }

        if ($note->resolved) {
            return response()->json(['message' => 'Note is already resolved.'], 422);
        }

        $note->update([
            'resolved' => true,
            'resolved_by' => $user->id,
            'resolution_message' => $request->resolution_message,
        ]);

        // Create resolution reply
        $markdownService = app(MarkdownService::class);
        Note::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $note->id,
            'body' => $markdownService->parse($request->resolution_message),
            'body_markdown' => $request->resolution_message,
            'notetype' => 'message',
        ]);

        app(TicketPulseService::class)->invalidatePulse($ticket->id);

        $note->load(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);

        return response()->json([
            'message' => 'Note resolved successfully',
            'note' => $this->formatNote($note, $user->id),
        ]);
    }

    public function editNote(Request $request, $id, $noteId)
    {
        $request->validate([
            'body' => 'required|string|max:65535',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        // Author-only
        if ((int) $note->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden: only the author can edit this note'], 403);
        }

        // Decision immutability
        if ($note->notetype === 'decision') {
            return response()->json([
                'message' => 'Decisions cannot be edited. Create a new decision that supersedes the original.',
            ], 422);
        }

        $markdownService = app(MarkdownService::class);
        $mentionService = app(MentionService::class);

        $note->update([
            'body' => $markdownService->parse($request->body),
            'body_markdown' => $request->body,
            'edited_at' => now(),
        ]);

        // Re-parse mentions
        $note->mentions()->delete();
        $mentionUsernames = $mentionService->parseMentions($request->body);
        $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
        $mentionService->createMentions($note, $mentionUserIds);

        $note->load(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);

        return response()->json([
            'message' => 'Note updated successfully',
            'note' => $this->formatNote($note, $user->id),
        ]);
    }

    public function reply(Request $request, $id, $noteId)
    {
        $request->validate([
            'body' => 'required|string|max:65535',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $parent = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        // Reject nested replies
        if ($parent->parent_id !== null) {
            return response()->json([
                'message' => 'Cannot reply to a reply. Replies must be on top-level notes.',
            ], 422);
        }

        $markdownService = app(MarkdownService::class);
        $mentionService = app(MentionService::class);

        $bodyHtml = $markdownService->parse($request->body);

        $reply = Note::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body' => $bodyHtml,
            'body_markdown' => $request->body,
            'notetype' => 'message',
        ]);

        $mentionUsernames = $mentionService->parseMentions($request->body);
        $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
        $mentionService->createMentions($reply, $mentionUserIds);

        $reply->load(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);

        return response()->json([
            'message' => 'Reply added successfully',
            'note' => $this->formatNote($reply, $user->id),
        ]);
    }

    public function react(Request $request, $id, $noteId)
    {
        $request->validate([
            'emoji' => 'required|string|in:'.implode(',', NoteReaction::ALLOWED_EMOJIS),
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('id', $id)->where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->firstOrFail();
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        $existing = NoteReaction::where('note_id', $note->id)
            ->where('user_id', $user->id)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            NoteReaction::create([
                'note_id' => $note->id,
                'user_id' => $user->id,
                'emoji' => $request->emoji,
            ]);
        }

        $reactions = NoteReaction::where('note_id', $note->id)
            ->get()
            ->groupBy('emoji')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'reacted' => $group->contains('user_id', $user->id),
            ])
            ->toArray();

        return response()->json(['reactions' => $reactions]);
    }

    protected function extractMentionsFromText(string $text): array
    {
        preg_match_all('/@([\w.\-]+)/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
