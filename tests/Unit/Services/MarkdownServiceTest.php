<?php

namespace Tests\Unit\Services;

use App\Services\MarkdownService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkdownServiceTest extends TestCase
{
    #[Test]
    public function it_resolves_ticket_cross_references(): void
    {
        $service = new MarkdownService;
        $result = $service->parse('See ticket #42 for details');

        $this->assertStringContainsString('href="/tickets/42"', $result);
    }

    #[Test]
    public function it_resolves_kb_cross_references(): void
    {
        $service = new MarkdownService;
        $result = $service->parse('Read kb:architecture-overview for context');

        $this->assertStringContainsString('href="/kb/architecture-overview"', $result);
    }
}
