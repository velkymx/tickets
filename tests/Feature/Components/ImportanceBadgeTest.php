<?php

namespace Tests\Feature\Components;

use App\Models\Importance;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ImportanceBadgeTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function component_renders_icon_for_importance(): void
    {
        $importance = Importance::factory()->create([
            'name' => 'Critical',
            'icon' => 'fa-solid fa-fire',
            'class' => 'danger',
        ]);

        $html = Blade::render('<x-importance-badge :importance="$importance" />', [
            'importance' => $importance,
        ]);

        $this->assertStringContainsString($importance->icon, $html);
        $this->assertStringContainsString('text-'.$importance->class, $html);
    }

    #[Test]
    public function component_renders_fallback_without_importance(): void
    {
        $html = Blade::render('<x-importance-badge :importance="$importance" />', [
            'importance' => null,
        ]);

        $this->assertStringContainsString('Priority: none', $html);
    }

    #[Test]
    public function ticket_show_uses_badge_component_for_importance(): void
    {
        $show = file_get_contents(resource_path('views/tickets/show.blade.php'));

        $this->assertStringContainsString('x-importance-badge', $show);
        $this->assertStringNotContainsString('$ticket->importance->class', $show);
        $this->assertStringNotContainsString('$ticket->importance->icon', $show);
    }
}
