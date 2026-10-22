<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use App\Services\DecisionRequestService;
use App\Services\MarkdownService;
use App\Services\MentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
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

        $markdownService = app(MarkdownService::class);
        $mentionService = app(MentionService::class);

        $isDecision = (bool) preg_match('/^\/decision\s+(.+)$/s', trim($validated['body']), $decision);
        $body = $isDecision ? trim($decision[1]) : $validated['body'];

        $reply = Note::create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'parent_id' => $parent->id,
            'body' => $markdownService->parse($body),
            'body_markdown' => $body,
            'notetype' => $isDecision ? 'decision' : 'message',
        ]);

        app(DecisionRequestService::class)->closeWith($reply);

        $mentionUsernames = $mentionService->parseMentions($validated['body']);
        $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
        $mentionService->createMentions($reply, $mentionUserIds);

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
