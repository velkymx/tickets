<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Importance;
use App\Services\MarkdownService;
use App\Services\MentionService;
use App\Services\SlashCommandService;
use App\Services\TicketPulseService;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
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
        ])
            ->where('user_id2', $user->id)
            ->findOrFail($id);

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

        $ticket = Ticket::where('user_id2', $user->id)->orWhere('user_id', $user->id)->findOrFail($id);

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
                    return response()->json([
                        'message' => 'Actions require exactly one @assignee',
                    ], 422);
                }
            }

            $commandResult = $slashService->handle($ticket, $bodyText);
            $warnings = $commandResult['warnings'] ?? [];

            // Check for blocker constraint from slash service
            $noteType = $commandResult['note_type'] ?? 'message';
            $changes = $commandResult['changes'] ?? [];
            foreach ($changes as $change) {
                if (str_contains($change, 'Resolve blocker before')) {
                    return response()->json([
                        'message' => $change,
                    ], 422);
                }
                if (str_contains($change, 'Too many open actions')) {
                    return response()->json([
                        'message' => $change,
                    ], 422);
                }
            }

            $bodyText = $commandResult['body'] ?? '';
            $bodyMarkdown = $markdownService->parse($bodyText);

            $createdNote = Note::create([
                'user_id' => $user->id,
                'ticket_id' => $ticket->id,
                'body' => $bodyText,
                'body_markdown' => $bodyMarkdown,
                'hours' => ($request->hours ?? 0) + ($commandResult['hours'] ?? 0),
                'notetype' => $noteType,
                'pinned' => $commandResult['note_attributes']['pinned'] ?? false,
            ]);

            // Create mention records
            $mentionUsernames = $mentionService->parseMentions($bodyText);
            $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
            $mentionService->createMentions($createdNote, $mentionUserIds);

            $createdNote->load(['user', 'replies.user', 'reactions', 'attachments', 'mentions.user']);
        }

        $ticket->load(['status', 'assignee']);

        $response = [
            'message' => 'Note added successfully',
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

    public function editNote(Request $request, $id, $noteId)
    {
        $request->validate([
            'body' => 'required|string|max:65535',
        ]);

        $user = $request->attributes->get('api_user');
        $ticket = Ticket::where('user_id2', $user->id)->orWhere('user_id', $user->id)->findOrFail($id);
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
            'body' => $request->body,
            'body_markdown' => $markdownService->parse($request->body),
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
        $ticket = Ticket::where('user_id2', $user->id)->orWhere('user_id', $user->id)->findOrFail($id);
        $parent = Note::where('ticket_id', $ticket->id)->findOrFail($noteId);

        // Reject nested replies
        if ($parent->parent_id !== null) {
            return response()->json([
                'message' => 'Cannot reply to a reply. Replies must be on top-level notes.',
            ], 422);
        }

        $markdownService = app(MarkdownService::class);
        $mentionService = app(MentionService::class);

        $bodyMarkdown = $markdownService->parse($request->body);

        $reply = Note::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body' => $request->body,
            'body_markdown' => $bodyMarkdown,
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
        $ticket = Ticket::where('user_id2', $user->id)->orWhere('user_id', $user->id)->findOrFail($id);
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
