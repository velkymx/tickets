<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Close out a ticket: resolve open threads, record the changelog, then move status.')]
class CloseOutTicketPrompt extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('ticket_id', 'The ticket to close out.', true),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|min:1',
        ]);

        $ticketId = $validated['ticket_id'];

        return Response::text(
            "Close out ticket {$ticketId} in this order:\n".
            "1. Call get-pulse-tool and resolve every open thread with resolve-note-tool, one resolution message each.\n".
            "2. Record what shipped with add-note-tool using the /decision command so the log is immutable.\n".
            "3. Move status with update-ticket-tool to a closed status. Closing sets closed_at automatically.\n".
            '4. Never close a ticket with unresolved threads unless a human explicitly says so.'
        );
    }
}
