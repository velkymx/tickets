<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Triage a ticket: check pulse health first, then act on what it reports.')]
class TriageTicketPrompt extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('ticket_id', 'The ticket to triage.', true),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|min:1',
        ]);

        $ticketId = $validated['ticket_id'];

        return Response::text(
            "Triage ticket {$ticketId}. Work in this order and stop at the first blocking item:\n".
            "1. Call get-pulse-tool for ticket {$ticketId}. If BLOCKED, read latest_blocker and resolve it before anything else.\n".
            "2. Read open_threads. Each thread is awaiting a human decision: summarize what is being asked, do not decide silently.\n".
            "3. Follow next_action. If it names someone else, reassign with update-ticket-tool instead of doing the work.\n".
            '4. Record what you did with add-note-tool so the next agent sees it in pulse.'
        );
    }
}
