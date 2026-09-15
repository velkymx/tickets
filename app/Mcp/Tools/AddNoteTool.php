<?php

namespace App\Mcp\Tools;

use App\Models\Note;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Services\MarkdownService;
use App\Services\MentionService;
use App\Services\SlashCommandService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add a note to a ticket you own or are assigned to. Supports slash commands in the body (/close, /status, /hours, /blocker, /decision, /action @user, /estimate). Optionally log hours, change status, or claim the ticket.')]
class AddNoteTool extends TicketTool
{
    public function handle(Request $request): Response
    {
        $user = $this->apiUser($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'body' => 'nullable|string|max:65535',
            'hours' => 'nullable|numeric|min:0|max:999',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'claim' => 'nullable|boolean',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        if (! empty($validated['claim'])) {
            $ticket->user_id2 = $user->id;
            $ticket->save();
        }

        if (! empty($validated['status_id']) && $validated['status_id'] != $ticket->status_id) {
            $ticket->status_id = $validated['status_id'];
            $ticket->closed_at = Status::isClosed($validated['status_id']) ? now() : null;
            $ticket->save();
        }

        $createdNote = null;
        $warnings = [];
        $bodyText = $validated['body'] ?? '';

        if (array_key_exists('body', $validated) || array_key_exists('hours', $validated)) {
            if (preg_match('/^\/action\b/m', $bodyText)) {
                preg_match_all('/@([\w.\-]+)/', $bodyText, $matches);
                $mentions = array_values(array_unique($matches[1] ?? []));
                if (count($mentions) !== 1) {
                    return Response::error('Actions require exactly one @assignee.');
                }
            }

            $slashService = app(SlashCommandService::class);
            $markdownService = app(MarkdownService::class);
            $mentionService = app(MentionService::class);

            $commandResult = $slashService->handle($ticket, $bodyText);
            $warnings = $commandResult['warnings'] ?? [];

            foreach ($commandResult['changes'] ?? [] as $change) {
                if (str_contains($change, 'Resolve blocker before') || str_contains($change, 'Too many open actions')) {
                    return Response::error($change);
                }
            }

            $parsedBody = $commandResult['body'] ?? '';
            $parsedHtml = $markdownService->parse($parsedBody);
            $totalHours = ($validated['hours'] ?? 0) + ($commandResult['hours'] ?? 0);

            // Skip commands that leave no text behind (e.g. /estimate, /close).
            if (trim(strip_tags($parsedHtml)) !== '' || $totalHours > 0) {
                $createdNote = Note::create([
                    'user_id' => $user->id,
                    'ticket_id' => $ticket->id,
                    'body' => $parsedHtml,
                    'body_markdown' => $parsedBody,
                    'hours' => $totalHours,
                    'notetype' => $commandResult['note_type'] ?? 'message',
                    'pinned' => $commandResult['note_attributes']['pinned'] ?? false,
                ]);

                $mentionUsernames = $mentionService->parseMentions($parsedBody);
                $mentionUserIds = User::whereIn('name', $mentionUsernames)->pluck('id')->toArray();
                $mentionService->createMentions($createdNote, $mentionUserIds);
            }
        }

        $ticket->load(['status', 'assignee']);

        $result = [
            'message' => $createdNote ? 'Note added.' : 'Command applied, no note stored.',
            'warnings' => $warnings,
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status->name ?? null,
                'assignee' => $ticket->assignee->name ?? null,
            ],
        ];

        if ($createdNote) {
            $result['note'] = [
                'id' => $createdNote->id,
                'notetype' => $createdNote->notetype,
                'hours' => $createdNote->hours,
            ];
        }

        return Response::json($result);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'body' => $schema->string()->description('Note text. Supports slash commands like /close, /blocker, /decision, /action @user task.'),
            'hours' => $schema->number()->description('Hours to log against the ticket.'),
            'status_id' => $schema->integer()->description('Move the ticket to this status ID.'),
            'claim' => $schema->boolean()->description('Assign the ticket to yourself.'),
        ];
    }
}
