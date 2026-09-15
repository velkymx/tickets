<?php

namespace App\Mcp\ServerMethods;

use Laravel\Mcp\Server\Contracts\Method;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Transport\JsonRpcRequest;
use Laravel\Mcp\Transport\JsonRpcResponse;

/**
 * Acknowledge the client's initialized notification when sent as a request.
 * Proper notifications (no id) never reach method handlers.
 */
class InitializedAck implements Method
{
    public function handle(JsonRpcRequest $request, ServerContext $context): JsonRpcResponse
    {
        return JsonRpcResponse::result($request->id, []);
    }
}
