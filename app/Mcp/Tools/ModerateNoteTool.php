<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Pin, unpin, hide, or unhide a note on a ticket you own or are assigned to. Pinned notes surface at the top; hidden notes are removed from the thread.')]
class ModerateNoteTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'note_id' => 'required|integer',
            'action' => 'required|in:pin,unpin,hide,unhide',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $note = Note::where('ticket_id', $ticket->id)->find($validated['note_id']);

        if (! $note) {
            return $this->noteNotFound();
        }

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

        return Response::json([
            'message' => 'Note updated.',
            'note' => [
                'id' => $note->id,
                'pinned' => (bool) $note->pinned,
                'hidden' => (bool) $note->hide,
            ],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The note ID to moderate.')->required(),
            'action' => $schema->string()->description('pin, unpin, hide, or unhide.')->required(),
        ];
    }
}
