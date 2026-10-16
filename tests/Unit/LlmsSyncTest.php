<?php

namespace Tests\Unit;

use App\Mcp\Servers\TicketsServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LlmsSyncTest extends TestCase
{
    #[Test]
    public function llms_names_every_api_ticket_endpoint(): void
    {
        $text = file_get_contents(base_path('llms.txt'));

        $paths = collect(Route::getRoutes())
            ->map(fn ($route) => '/'.ltrim($route->uri(), '/'))
            ->filter(fn ($uri) => str_starts_with($uri, '/api/v1/'))
            ->unique()
            ->values();

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $template = preg_replace('#\{[^}]+\}#', '{id}', $path);
            $this->assertStringContainsString($template, $text, "llms.txt missing {$template}");
        }
    }

    #[Test]
    public function llms_names_every_mcp_tool_and_prompt(): void
    {
        $text = file_get_contents(base_path('llms.txt'));

        $defaults = (new \ReflectionClass(TicketsServer::class))->getDefaultProperties();

        $classes = $defaults['tools'] ?? [];

        $this->assertNotEmpty($classes);

        foreach (array_merge($classes, $defaults['prompts'] ?? []) as $class) {
            $wire = Str::kebab(class_basename($class));
            $this->assertStringContainsString($wire, $text, "llms.txt missing {$wire}");
        }
    }

    #[Test]
    public function llms_names_no_phantom_tools_or_prompts(): void
    {
        $text = file_get_contents(base_path('llms.txt'));

        $defaults = (new \ReflectionClass(TicketsServer::class))->getDefaultProperties();

        $registered = collect([$defaults['tools'] ?? [], $defaults['prompts'] ?? []])
            ->flatten()
            ->map(fn ($class) => Str::kebab(class_basename($class)))
            ->all();

        preg_match_all('/`([a-z0-9-]+-(?:tool|prompt))`/', $text, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $referenced) {
            $this->assertContains($referenced, $registered, "llms.txt references unregistered {$referenced}");
        }
    }

    #[Test]
    public function llms_covers_workflows_and_demo_seeder(): void
    {
        $text = file_get_contents(base_path('llms.txt'));

        foreach (['pulse-before-act', 'lookups-first', 'DemoSeeder'] as $needle) {
            $this->assertStringContainsString($needle, $text, "llms.txt missing {$needle}");
        }
    }

    #[Test]
    public function llms_rate_limit_matches_registered_limiter(): void
    {
        $text = file_get_contents(base_path('llms.txt'));

        $limit = call_user_func(RateLimiter::limiter('api'), Request::create('/api/v1/tickets'));
        $entries = is_array($limit) ? $limit : [$limit];

        $this->assertNotEmpty($entries);

        foreach ($entries as $entry) {
            $this->assertSame(60, $entry->maxAttempts);
        }

        $this->assertStringContainsString('60 requests per minute', $text);
    }
}
