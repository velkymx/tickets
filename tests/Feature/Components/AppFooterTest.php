<?php

namespace Tests\Feature\Components;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AppFooterTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function footer_shows_status_badges_and_copyright(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('actions/workflows/tests.yml/badge.svg?branch=master', false);
        $response->assertSee('Techno Sorcery Corporation', false);
    }
}
