<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class TicketTool extends Tool
{
    /**
     * Resolve the calling API user from the auth context.
     *
     * Web requests authenticate via the api.token middleware. Local (stdio)
     * servers have no HTTP layer, so they fall back to MCP_USER_ID when set.
     */
    protected function apiUser(Request $request): ?User
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $user;
        }

        if (app()->runningInConsole() && ($id = env('MCP_USER_ID'))) {
            return User::find($id);
        }

        return null;
    }

    protected function unauthenticated(): Response
    {
        return Response::error('Unauthenticated. Provide an API token (web) or set MCP_USER_ID (local).');
    }

    /**
     * Find a ticket the user owns or is assigned to. Null when missing
     * or foreign, so callers return a clean not-found error either way.
     */
    protected function findTicket(User $user, int $id): ?Ticket
    {
        return Ticket::where(function ($q) use ($user) {
            $q->where('user_id2', $user->id)->orWhere('user_id', $user->id);
        })->find($id);
    }

    protected function ticketNotFound(): Response
    {
        return Response::error('Ticket not found.');
    }

    protected function noteNotFound(): Response
    {
        return Response::error('Note not found on this ticket.');
    }
}
