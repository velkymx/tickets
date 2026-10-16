<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Build a standup digest for a milestone: health per ticket plus what is blocked and who owns it.')]
class StandupDigestPrompt extends Prompt
{
    public function arguments(): array
    {
        return [
            new Argument('milestone_id', 'The milestone to report on.', true),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'milestone_id' => 'required|integer|min:1',
        ]);

        $milestoneId = $validated['milestone_id'];

        return Response::text(
            "Write a standup digest for milestone {$milestoneId}:\n".
            "1. Call list-milestones-tool to confirm scope, then get-milestone-tool for its tickets.\n".
            "2. Call get-pulse-tool per open ticket and group results: BLOCKED (with blocker reason and owner), AT RISK (with why), ON TRACK.\n".
            '3. Keep it short: one line per ticket, owners named, decisions needed listed last so they stand out. Notes attach to tickets, not milestones, so record follow-ups on the relevant ticket.'
        );
    }
}
