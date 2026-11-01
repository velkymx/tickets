<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Services\DecisionRequestService;
use App\Services\TicketService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Reply to a top-level note on a ticket you own or are assigned to. Cannot reply to a reply. Start the body with /decision to record a decision; on a decision request thread that also closes the request.')]
class ReplyToNoteTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        Auth::setUser($user);

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'note_id' => 'required|integer',
            'body' => 'required|string|max:65535',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }
        $parent = Note::where('ticket_id', $ticket->id)->find($validated['note_id']);

        if (! $parent) {
            return $this->noteNotFound();
        }

        if ($parent->parent_id !== null) {
            return Response::error('Cannot reply to a reply. Replies must be on top-level notes.');
        }

        $isDecision = (bool) preg_match('/^\/decision\s+(.+)$/s', trim($validated['body']), $decision);
        $body = $isDecision ? trim($decision[1]) : $validated['body'];

        $ticketService = app(TicketService::class);
        $ticketService->notate((int) $ticket->id, $body, [], 0, (int) $parent->id);

        $reply = Note::query()
            ->where('ticket_id', $ticket->id)
            ->where('parent_id', $parent->id)
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        if ($isDecision) {
            $reply->update(['notetype' => 'decision']);
            app(DecisionRequestService::class)->closeWith($reply);
        }

        return $this->respond([
            'message' => 'Reply added.',
            'note' => ['id' => $reply->id, 'parent_id' => $parent->id],
        ], 'Reply added. Call get-ticket-tool to read the thread in context.');
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'note_id' => $schema->integer()->description('The top-level note ID to reply to.')->required(),
            'body' => $schema->string()->description('Reply text.')->required(),
        ];
    }
}
