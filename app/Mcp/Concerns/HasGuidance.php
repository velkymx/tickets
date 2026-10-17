<?php

namespace App\Mcp\Concerns;

use Laravel\Mcp\Response;

trait HasGuidance
{
    /**
     * Return a JSON payload with an additive guidance key carrying the
     * one or two calls that usually follow. Data keys stay stable.
     * Union keeps the payload authoritative on collision: respond()
     * can add guidance but never overwrite a tool's own data key.
     */
    protected function respond(array $payload, string $guidance): Response
    {
        return Response::json($payload + ['guidance' => $guidance]);
    }
}
