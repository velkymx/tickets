<?php

namespace App\Mcp\Tools;

use App\Models\KbCategory;
use App\Models\KbTag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type as JsonType;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get knowledge base category and tag IDs. Call this first to resolve category and tag names to IDs before creating or updating articles.')]
#[IsReadOnly]
#[IsIdempotent]
class GetKbLookupsTool extends KbTool
{
    public function handle(Request $request): Response
    {
        if (! $this->apiUser($request)) {
            return $this->unauthenticated();
        }

        return Response::json([
            'categories' => KbCategory::orderBy('sort_order')->get(['id', 'name']),
            'tags' => KbTag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @return array<string, JsonType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
