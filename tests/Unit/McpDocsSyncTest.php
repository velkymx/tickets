<?php

namespace Tests\Unit;

use App\Mcp\Servers\TicketsServer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpDocsSyncTest extends TestCase
{
    #[Test]
    public function mcp_guide_tool_count_matches_registered_tools(): void
    {
        $tools = (new \ReflectionClass(TicketsServer::class))->getDefaultProperties()['tools'] ?? [];

        $this->assertNotEmpty($tools);

        $text = (string) file_get_contents(base_path('docs/mcp.md'));

        $this->assertStringContainsString(
            'returns all '.count($tools).' tools',
            $text,
            'docs/mcp.md tool count drifted from the registered server list'
        );
    }
}
