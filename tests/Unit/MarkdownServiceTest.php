<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\MarkdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkdownServiceTest extends TestCase
{
    use RefreshDatabase;

    private MarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarkdownService();
    }

    #[Test]
    public function it_parses_standard_markdown()
    {
        $input = "## Heading\n\n* List item\n* List item 2";
        $output = $this->service->parse($input);

        $this->assertStringContainsString('<h2>Heading</h2>', $output);
        $this->assertStringContainsString('<li>List item</li>', $output);
    }

    #[Test]
    public function it_converts_mentions_to_links()
    {
        $user = User::factory()->create(['name' => 'JohnDoe']);
        $input = "Hello @JohnDoe!";
        
        $output = $this->service->parse($input);

        $this->assertStringContainsString(
            '<a class="mention" href="/users/' . $user->id . '">@JohnDoe</a>',
            $output
        );
    }

    #[Test]
    public function it_highlights_slash_commands()
    {
        $input = "/status Testing";
        $output = $this->service->parse($input);

        $this->assertStringContainsString('<code class="slash-command">/status Testing</code>', $output);
    }

    #[Test]
    public function it_renders_fenced_code_blocks(): void
    {
        $input = "```php\nthrow new RuntimeException('boom');\n```";

        $output = $this->service->parse($input);

        $this->assertStringContainsString('<pre><code class="language-php">', $output);
        $this->assertStringContainsString('throw new RuntimeException', $output);
    }

    #[Test]
    public function it_renders_interactive_checklists(): void
    {
        $input = "- [ ] First task\n- [x] Done task";

        $output = $this->service->parse($input);

        $this->assertStringContainsString('class="checklist-item"', $output);
        $this->assertStringContainsString('type="checkbox"', $output);
        $this->assertStringContainsString('First task', $output);
        $this->assertStringContainsString('checked disabled', $output);
    }

    #[Test]
    public function it_auto_wraps_stack_traces_in_code_fences(): void
    {
        $input = "RuntimeException: Boom\n#0 /app/Service.php(10): App\\\\Service->handle()\n#1 {main}";

        $output = $this->service->parse($input);

        $this->assertStringContainsString('<pre><code>', $output);
        $this->assertStringContainsString('RuntimeException: Boom', $output);
        $this->assertStringContainsString('#0 /app/Service.php(10)', $output);
    }
}
