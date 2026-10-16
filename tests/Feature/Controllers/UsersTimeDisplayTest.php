<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UsersTimeDisplayTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function profile_shows_twelve_hour_time_in_the_morning(): void
    {
        $this->travelTo(Carbon::parse('2026-01-15 09:30', 'America/New_York'));
        $user = User::factory()->create(['timezone' => 'America/New_York']);

        $response = $this->actingAs($user)->get("/users/{$user->id}");
        $response->assertStatus(200);

        $this->assertSame('09:30 (9:30 am)', $response->viewData('currenttime'));
    }

    #[Test]
    public function profile_shows_twelve_hour_time_in_the_afternoon(): void
    {
        $this->travelTo(Carbon::parse('2026-01-15 14:05', 'America/New_York'));
        $user = User::factory()->create(['timezone' => 'America/New_York']);

        $response = $this->actingAs($user)->get("/users/{$user->id}");
        $response->assertStatus(200);

        $this->assertSame('14:05 (2:05 pm)', $response->viewData('currenttime'));
    }

    #[Test]
    public function timezone_picker_shows_twelve_hour_time_in_the_morning(): void
    {
        $this->travelTo(Carbon::parse('2026-01-15 09:30', 'America/New_York'));
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/user/edit');
        $response->assertStatus(200);

        $timezones = $response->viewData('timezones');
        $this->assertStringEndsWith('(9:30 am)', $timezones['America']['America/New_York']);
    }
}
