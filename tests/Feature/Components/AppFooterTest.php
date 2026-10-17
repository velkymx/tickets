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
        $response->assertSee('<a href="https://blog.ajb.bz/" target="_blank" rel="noopener noreferrer">Alan Bollinger</a>', false);
        $response->assertDontSee('Techno Sorcery Corporation', false);
    }

    #[Test]
    public function footer_shows_installed_version(): void
    {
        config()->set('app.version', 'v9.9.9-test');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('v9.9.9-test', false);
    }
}
