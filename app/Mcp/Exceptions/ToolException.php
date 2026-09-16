<?php

namespace App\Mcp\Exceptions;

use RuntimeException;

/**
 * Signals a business-rule failure inside a tool's DB::transaction closure.
 *
 * Thrown to roll back partial writes, then caught within the tool's handle()
 * and converted to Response::error() — so it never escapes the handler and
 * bypasses MCP error serialization.
 */
class ToolException extends RuntimeException
{
}
