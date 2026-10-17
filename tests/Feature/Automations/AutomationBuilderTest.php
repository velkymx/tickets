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

        $response->assertSee('x-data="conditionBuilder', false);
        $response->assertSee('x-data="actionBuilder', false);
        $response->assertSee('data-initial', false);
    }
}
