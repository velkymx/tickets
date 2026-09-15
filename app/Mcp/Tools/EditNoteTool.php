<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MarkdownService;
use App\Services\MentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Edit your own note. Decisions are immutable and cannot be edited — create a new decision instead.')]
class EditNoteTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'note_id' => 'required|integer|exists:notes,id',
            'body' => 'required|string|max:65535',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }
        $note = Note::where('ticket_id', $ticket->id)->find($validated['note_id']);

        if (! $note) {
            return $this->noteNotFound();
        }

        if ((int) $note->user_id !== (int) $user->id) {
            return Response::error('Forbidden: only the author can edit this note.');
        }

        if ($note->notetype === 'decision') {
            return Response::error('Decisions cannot be edited. Create a new decision that supersedes the original.');
        }

        $markdownService = app(MarkdownService::class);
        $mentionService = app(MentionService::class);

        $note->update([
            'body' => $markdownService->parse($validated['body']),
            'body_markdown' => $validated['body'],
            'edited_at' => now(),
        ]);

        $note->mentions()->delete();
        $mentionUsernames = $mentionService->parseMentions($validated['body']);
        $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
        $mentionService->createMentions($note, $mentionUserIds);

        return Response::json([
            'message' => 'Note updated.',
            'note' => ['id' => $note->id],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('Your note ID to edit.')->required(),
            'body' => $schema->string()->description('New note text.')->required(),
        ];
    }
}
