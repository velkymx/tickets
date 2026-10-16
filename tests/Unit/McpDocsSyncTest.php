<?php

namespace Tests\Unit;

use App\Mcp\Servers\TicketsServer;
use Illuminate\Support\Str;
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

    #[Test]
    public function guidance_text_names_only_registered_tools(): void
    {
        $registered = collect((new \ReflectionClass(TicketsServer::class))->getDefaultProperties()['tools'] ?? [])
            ->map(fn ($class) => Str::kebab(class_basename($class)))
            ->all();

        $this->assertNotEmpty($registered);

        $files = glob(app_path('Mcp/Tools/*Tool.php')) ?: [];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);

            preg_match_all('/\b[a-z0-9-]+-tool\b/', $source, $matches);

            foreach (array_unique($matches[0]) as $referenced) {
                $this->assertContains($referenced, $registered, basename($file)." references unregistered {$referenced}");
            }
        }
    }
}
