<?php

namespace Tests\Unit\Automations\Support;

use App\Automations\Support\PayloadRenderer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayloadRendererTest extends TestCase
{
    #[Test]
    public function it_renders_flat_template(): void
    {
        $renderer = new PayloadRenderer();
        $result = $renderer->render(
            ['ticket_id' => '{{ ticket.id }}', 'title' => '{{ ticket.subject }}'],
            ['ticket' => ['id' => 42, 'subject' => 'Bug report']]
        );
        $this->assertEquals(['ticket_id' => '42', 'title' => 'Bug report'], $result);
    }

    #[Test]
    public function it_renders_nested_template(): void
    {
        $renderer = new PayloadRenderer();
        $result = $renderer->render(
            ['data' => ['id' => '{{ ticket.id }}']],
            ['ticket' => ['id' => 7]]
        );
        $this->assertEquals(['data' => ['id' => '7']], $result);
    }

    #[Test]
    public function it_leaves_unknown_variables_blank(): void
    {
        $renderer = new PayloadRenderer();
        $result = $renderer->render(
            ['val' => '{{ ticket.nonexistent }}'],
            ['ticket' => ['id' => 1]]
        );
        $this->assertEquals(['val' => ''], $result);
    }
}
