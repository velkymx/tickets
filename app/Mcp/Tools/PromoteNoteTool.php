<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Services\NotePromotionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Promote a plain message note into a decision, blocker, action, or decision request (decision_needed) on a ticket you own or are assigned to. Decisions need at least 20 characters; actions need exactly one @assignee and decision requests exactly one @decider (a mention in the body or the assignee argument).')]
class PromoteNoteTool extends TicketTool
{
    public function handle(Request $request, NotePromotionService $promotion): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'note_id' => 'required|integer',
            'type' => 'required|in:decision,blocker,action,decision_needed',
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

        $error = $promotion->promote($note, $validated['type'], $validated['assignee'] ?? null);

        if ($error) {
            return Response::error($error);
        }

        if ($note->notetype === 'decision_needed') {
            return $this->respond([
                'message' => 'Note promoted.',
                'note' => ['id' => $note->id, 'notetype' => $note->notetype, 'decider_id' => $note->decider_id],
            ], 'Decision requested. The decider was notified; a /decision reply on this thread closes it.');
        }

        return $this->respond([
            'message' => 'Note promoted.',
            'note' => ['id' => $note->id, 'notetype' => $note->notetype],
        ], 'Note promoted. Call get-ticket-tool to verify placement.');
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The message note ID to promote.')->required(),
            'type' => $schema->string()->description('decision, blocker, action, or decision_needed.')->required(),
            'assignee' => $schema->string()->description('Username to assign when promoting to an action, or who must decide for decision_needed (if not already @mentioned).'),
        ];
    }
}
