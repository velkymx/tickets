<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class HealthPageTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function admin_sees_all_nine_sections(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/health');

        $response->assertOk();

        foreach (['System info', 'PHP requirements', 'Features', 'Queue', 'Mail', 'Cache and session', 'Security and config', 'Storage', 'Database'] as $section) {
            $response->assertSee($section);
        }
    }

    #[Test]
    public function non_admin_gets_forbidden_on_all_health_routes(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $this->actingAs($user)->get('/admin/health')->assertForbidden();
        $this->actingAs($user)->postJson('/admin/health/queue-ping')->assertForbidden();
        $this->actingAs($user)->getJson('/admin/health/queue-ping/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')->assertForbidden();
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get('/admin/health')->assertRedirect('/login');
    }

    #[Test]
    public function dropdown_shows_system_health_after_manage_users_for_admins(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)->get('/home')->assertOk()->assertSeeInOrder(['Manage Users', 'System Health']);
    }

    #[Test]
    public function dropdown_hides_system_health_from_non_admins(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $this->actingAs($user)->get('/home')->assertOk()->assertDontSee('System Health');
    }

    #[Test]
    public function disabled_features_render_a_neutral_badge(): void
    {
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($admin)->get('/admin/health')
            ->assertSee('<span class="badge text-bg-secondary">Disabled</span>', false);
    }
}
