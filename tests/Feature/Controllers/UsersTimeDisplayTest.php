<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UsersTimeDisplayTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function profile_always_shows_twelve_hour_time(): void
    {
        $user = User::factory()->create(['timezone' => 'America/New_York']);

        $response = $this->actingAs($user)->get("/users/{$user->id}");
        $response->assertStatus(200);

        $currenttime = $response->viewData('currenttime');
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2} \(\d{1,2}:\d{2} [ap]m\)$/', $currenttime);
    }

    #[Test]
    public function timezone_picker_always_shows_twelve_hour_time(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/edit');
        $response->assertStatus(200);

        $timezones = $response->viewData('timezones');
        $this->assertNotEmpty($timezones['America']['America/New_York']);
        $this->assertMatchesRegularExpression(
            '/\(\d{1,2}:\d{2} [ap]m\)$/',
            $timezones['America']['America/New_York']
        );
    }

    #[Test]
    public function time_display_has_no_morning_gate_or_null_datetime(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/UsersController.php'));

        $this->assertStringNotContainsString("format('H') >= 12", $controller);
        $this->assertStringNotContainsString('new \DateTime(null', $controller);
    }
}
