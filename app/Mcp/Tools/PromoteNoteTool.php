<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Promote a plain message note into a decision, blocker, or action on a ticket you own or are assigned to. Decisions need at least 20 characters; actions need exactly one @assignee (a mention in the body or the assignee argument).')]
class PromoteNoteTool extends TicketTool
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
            'type' => 'required|in:decision,blocker,action',
            'assignee' => 'nullable|string',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $note = Note::where('ticket_id', $ticket->id)->find($validated['note_id']);

        if (! $note) {
            return $this->noteNotFound();
        }

        if ($note->notetype !== 'message') {
            return Response::error('Only message notes can be promoted.');
        }

        $type = $validated['type'];
        $body = strip_tags($note->body);
        $hasMention = (bool) preg_match('/@\[([^\]]+)\]/u', $note->body);

        if ($type === 'decision' && mb_strlen(trim($body)) < 20) {
            return Response::error('Decision notes must be at least 20 characters.');
        }

        if ($type === 'action' && ! $hasMention && empty($validated['assignee'])) {
            return Response::error('Actions require exactly one @assignee.');
        }

        // Append the assignee mention when supplied and not already present.
        if ($type === 'action' && ! empty($validated['assignee']) && ! $hasMention) {
            $assigneeName = ltrim((string) $validated['assignee'], '@');
            $note->body = rtrim($note->body).' @['.$assigneeName.']';
        }

        $note->notetype = $type;
        $note->save();

        return Response::json([
            'message' => 'Note promoted.',
            'note' => ['id' => $note->id, 'notetype' => $note->notetype],
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The message note ID to promote.')->required(),
            'type' => $schema->string()->description('decision, blocker, or action.')->required(),
            'assignee' => $schema->string()->description('Username to assign when promoting to an action (if not already @mentioned).'),
        ];
    }
}
