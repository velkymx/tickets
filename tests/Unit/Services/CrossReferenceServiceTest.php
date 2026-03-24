<?php

namespace Tests\Unit\Services;

use App\Services\CrossReferenceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CrossReferenceServiceTest extends TestCase
{
    private CrossReferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrossReferenceService;
    }

    #[Test]
    public function it_converts_ticket_references_to_links(): void
    {
        $html = '<p>See ticket #123 for details.</p>';
        $result = $this->service->resolve($html);

        $this->assertStringContainsString('href="/tickets/123"', $result);
        $this->assertStringContainsString('#123', $result);
    }

    #[Test]
    public function it_converts_kb_references_to_links(): void
    {
        $html = '<p>See kb:getting-started for more info.</p>';
        $result = $this->service->resolve($html);

        $this->assertStringContainsString('href="/kb/getting-started"', $result);
        $this->assertStringContainsString('kb:getting-started', $result);
    }

    #[Test]
    public function it_does_not_match_inside_code_blocks(): void
    {
        $html = '<code>#123</code>';
        $result = $this->service->resolve($html);

        $this->assertStringNotContainsString('href=', $result);
    }

    #[Test]
    public function it_does_not_match_inside_links(): void
    {
        $html = '<a href="/foo">#123</a>';
        $result = $this->service->resolve($html);

        // Should not double-wrap in a link
        $this->assertEquals(1, substr_count($result, '<a '));
    }

    #[Test]
    public function it_handles_multiple_references(): void
    {
        $html = '<p>See #1, #2, and kb:faq.</p>';
        $result = $this->service->resolve($html);

        $this->assertStringContainsString('href="/tickets/1"', $result);
        $this->assertStringContainsString('href="/tickets/2"', $result);
        $this->assertStringContainsString('href="/kb/faq"', $result);
    }
}
