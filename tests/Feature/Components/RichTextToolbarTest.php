<?php

namespace Tests\Feature\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RichTextToolbarTest extends TestCase
{
    #[Test]
    public function toolbar_preserves_selection_on_mousedown(): void
    {
        $bundle = file_get_contents(base_path('resources/js/components/rich-text.js'));

        $this->assertStringContainsString('mousedown', $bundle);
        $this->assertStringContainsString('preventDefault', $bundle);
    }
}
