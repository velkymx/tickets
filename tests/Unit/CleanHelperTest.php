<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CleanHelperTest extends TestCase
{
    #[Test]
    public function it_keeps_safe_formatting(): void
    {
        $out = clean('<p>Hello <strong>world</strong> <a href="https://example.com">link</a></p>');

        $this->assertStringContainsString('<strong>world</strong>', $out);
        $this->assertStringContainsString('href="https://example.com"', $out);
    }

    #[Test]
    public function it_strips_event_handler_attributes(): void
    {
        $out = clean('<img src="x" onerror="alert(1)"> <div onmouseover="steal()">hi</div>');

        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('onmouseover', $out);
    }

    #[Test]
    public function it_strips_javascript_urls(): void
    {
        $out = clean('<a href="javascript:alert(document.cookie)">click</a>');

        $this->assertStringNotContainsString('javascript:', $out);
    }

    #[Test]
    public function it_strips_script_tags(): void
    {
        $out = clean('safe<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert(1)', $out);
    }

    #[Test]
    public function it_strips_autofocus_and_onfocus_from_input(): void
    {
        $out = clean('<input autofocus onfocus="steal()">');

        $this->assertStringNotContainsString('autofocus', $out);
        $this->assertStringNotContainsString('onfocus', $out);
    }

    #[Test]
    public function it_keeps_rendered_task_list_checkboxes(): void
    {
        $out = clean('<li class="checklist-item"><input type="checkbox" checked disabled></li>');

        $this->assertStringContainsString('<input', $out);
        $this->assertStringContainsString('type="checkbox"', $out);
        $this->assertStringContainsString('class="checklist-item"', $out);
    }

    #[Test]
    public function it_keeps_pipeline_class_hooks(): void
    {
        $out = clean('<code class="slash-command">/close</code>');

        $this->assertStringContainsString('class="slash-command"', $out);
    }

    #[Test]
    public function it_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', clean(null));
        $this->assertSame('', clean(''));
    }
}
