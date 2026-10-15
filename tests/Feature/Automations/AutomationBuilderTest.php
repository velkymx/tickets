<?php

namespace Tests\Feature\Automations;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationBuilderTest extends TestCase
{
    use SeedsDatabase;
    #[Test]
    public function create_page_uses_registered_alpine_builders(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($admin)->get('/automations/create');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('x-data="conditionBuilder"', $html);
        $this->assertStringContainsString('x-data="actionBuilder"', $html);
        $this->assertStringContainsString('data-initial', $html);
        $this->assertStringNotContainsString('x-data="{', $html);
    }

    #[Test]
    public function builders_are_registered_in_the_bundle(): void
    {
        $bundle = file_get_contents(base_path('resources/js/features/automation-builders.js'));
        $this->assertStringContainsString('conditionBuilder', $bundle);
        $this->assertStringContainsString('actionBuilder', $bundle);
        $this->assertStringContainsString('serialize', $bundle);

        $app = file_get_contents(base_path('resources/js/app.js'));
        $this->assertStringContainsString('automation-builders', $app);
        $this->assertStringContainsString("Alpine.data('conditionBuilder'", $app);
        $this->assertStringContainsString("Alpine.data('actionBuilder'", $app);
    }
}
