<?php

namespace App\Mcp\Concerns;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait ResolvesApiUser
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

        // Local (stdio) servers have no HTTP auth layer, so resolve the acting
        // user from config (config/mcp.php reads MCP_USER_ID). Read via config()
        // rather than env() directly so it works under config:cache.
        if (app()->runningInConsole() && ($id = config('mcp.user_id'))) {
            return User::find($id);
        }

        return null;
    }

    protected function unauthenticated(): Response
    {
        return Response::error('Unauthenticated. Provide an API token (web) or set MCP_USER_ID (local).');
    }
}
