<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\MarkdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
    public function it_parses_standard_markdown()
    {
        $input = "## Heading\n\n* List item\n* List item 2";
        $output = $this->service->parse($input);

        $this->assertStringContainsString('<h2>Heading</h2>', $output);
        $this->assertStringContainsString('<li>List item</li>', $output);
    }

    /** @test */
    public function it_converts_mentions_to_links()
    {
        $user = User::factory()->create(['name' => 'JohnDoe']);
        $input = "Hello @JohnDoe!";
        
        $output = $this->service->parse($input);

        $this->assertStringContainsString(
            '<a href="/users/' . $user->id . '">@JohnDoe</a>', 
            $output
        );
    }

    /** @test */
    public function it_highlights_slash_commands()
    {
        $input = "/status Testing";
        $output = $this->service->parse($input);

        $this->assertStringContainsString('<code class="slash-command">/status Testing</code>', $output);
    }
}
