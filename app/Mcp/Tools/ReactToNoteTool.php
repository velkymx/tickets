<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Toggle an emoji reaction (thumbsup, eyes) on a note. Calling again removes it.')]
class ReactToNoteTool extends TicketTool
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
            'emoji' => 'required|string|in:'.implode(',', NoteReaction::ALLOWED_EMOJIS),
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }
        $note = Note::where('ticket_id', $ticket->id)->find($validated['note_id']);

        if (! $note) {
            return $this->noteNotFound();
        }

        $existing = NoteReaction::where('note_id', $note->id)
            ->where('user_id', $user->id)
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
            $toggled = 'removed';
        } else {
            NoteReaction::create([
                'note_id' => $note->id,
                'user_id' => $user->id,
                'emoji' => $validated['emoji'],
            ]);
            $toggled = 'added';
        }

        $reactions = NoteReaction::where('note_id', $note->id)
            ->get()
            ->groupBy('emoji')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'reacted' => $group->contains('user_id', $user->id),
            ])
            ->toArray();

        return Response::json([
            'message' => "Reaction {$toggled}.",
            'reactions' => $reactions,
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The note ID.')->required(),
            'emoji' => $schema->string()->description('thumbsup or eyes.')->required(),
        ];
    }
}
