<?php

namespace App\Mcp\Tools;

use App\Mcp\Exceptions\ToolException;
use App\Models\Status;
use App\Services\TicketService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add a note to a ticket you own or are assigned to. Supports slash commands in the body (/close, /status, /hours, /blocker, /decision, /decide @user, /action @user, /estimate). Use /decide @user question to ask one person for a decision; they are notified at once. Optionally log hours, change status, or claim the ticket.')]
class AddNoteTool extends TicketTool
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
            'body' => 'nullable|string|max:65535',
            'hours' => 'nullable|numeric|min:0|max:999',
            'status_id' => 'nullable|integer|exists:statuses,id',
            'claim' => 'nullable|boolean',
        ]);

        $ticket = $this->findTicket($user, $validated['ticket_id']);

        if (! $ticket) {
            return $this->ticketNotFound();
        }

        $bodyText = $validated['body'] ?? '';

        // All writes run in one transaction so a failed slash-command guard
        // rolls back the claim/status changes too. Business-rule failures are
        // thrown as ToolException and caught below, never escaping the handler.
        try {
            $result = DB::transaction(function () use ($user, $ticket, $validated, $bodyText) {
                $old = $ticket->toArray();

                if (! empty($validated['claim'])) {
                    $ticket->user_id2 = $user->id;
                    $ticket->save();
                }

                if (! empty($validated['status_id']) && (int) $validated['status_id'] !== (int) $ticket->status_id) {
                    $ticket->status_id = $validated['status_id'];
                    $ticket->closed_at = Status::isClosed($validated['status_id']) ? now() : null;
                    $ticket->save();
                }

                $ticketService = app(TicketService::class);
                $changes = $ticketService->changes($old, $ticket->fresh()->toArray());

                if (! array_key_exists('body', $validated) && ! array_key_exists('hours', $validated) && $changes === []) {
                    return ['note' => null, 'changes' => [], 'warnings' => []];
                }

                if (preg_match('/^\/action\b/m', $bodyText)) {
                    preg_match_all('/@([\w.\-]+)/', $bodyText, $matches);
                    $mentions = array_values(array_unique($matches[1] ?? []));
                    if (count($mentions) !== 1) {
                        throw new ToolException('Actions require exactly one @assignee.');
                    }
                }

                $result = $ticketService->notate(
                    (int) $ticket->id,
                    $bodyText,
                    $changes,
                    (float) ($validated['hours'] ?? 0),
                );

                foreach ($result['changes'] as $change) {
                    if (str_contains($change, 'Resolve blocker before') || str_contains($change, 'Too many open actions') || str_contains($change, 'Decision requests require')) {
                        throw new ToolException($change);
                    }
                }

                return $result;
            });
        } catch (ToolException $e) {
            return Response::error($e->getMessage());
        }

        $createdNote = $result['note'];
        $warnings = $result['warnings'];

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

        return $this->respond($result, 'Note recorded. Call get-pulse-tool to see the updated execution state.');
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->description('The ticket ID.')->required(),
            'body' => $schema->string()->description('Note text. Supports slash commands like /close, /blocker, /decision, /decide @user question, /action @user task.'),
            'hours' => $schema->number()->description('Hours to log against the ticket.'),
            'status_id' => $schema->integer()->description('Move the ticket to this status ID.'),
            'claim' => $schema->boolean()->description('Assign the ticket to yourself.'),
        ];
    }
}
