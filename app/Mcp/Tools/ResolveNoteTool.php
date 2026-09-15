<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Models\Ticket;
use App\Services\MarkdownService;
use App\Services\TicketPulseService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Resolve a blocker or action thread with a resolution message. Only the thread author or the ticket assignee can resolve.')]
class ResolveNoteTool extends TicketTool
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
            'resolution_message' => 'required|string|max:65535',
        ]);

        $ticket = Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->findOrFail($validated['ticket_id']);
        $note = Note::where('ticket_id', $ticket->id)->findOrFail($validated['note_id']);

        $isAuthor = (int) $note->user_id === (int) $user->id;
        $isAssignee = (int) $ticket->user_id2 === (int) $user->id;

        if (! $isAuthor && ! $isAssignee) {
            return Response::error('Forbidden: only the thread author or ticket assignee can resolve.');
        }

        $note->update([
            'resolved' => true,
            'resolved_by' => $user->id,
            'resolution_message' => $validated['resolution_message'],
        ]);

        Note::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $note->id,
            'body' => app(MarkdownService::class)->parse($validated['resolution_message']),
            'body_markdown' => $validated['resolution_message'],
            'notetype' => 'message',
        ]);

        app(TicketPulseService::class)->invalidatePulse($ticket->id);

        return Response::json([
            'message' => 'Note resolved.',
            'note' => ['id' => $note->id, 'resolved' => true],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The thread note ID to resolve.')->required(),
            'resolution_message' => $schema->string()->description('How the thread was resolved (required).')->required(),
        ];
    }
}
