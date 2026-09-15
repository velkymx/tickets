<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UserManagementTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }

    #[Test]
    public function admin_can_list_users(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $other = User::factory()->create(['name' => 'Jane Target']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('Jane Target');
    }

    #[Test]
    public function admin_can_update_a_users_access(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create(['admin' => false, 'active' => true, 'kb_role' => null]);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'admin' => 1,
            'active' => 0,
            'kb_role' => 'author',
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertTrue($user->admin);
        $this->assertFalse($user->active);
        $this->assertSame('author', $user->kb_role);
    }

    #[Test]
    public function admin_cannot_revoke_their_own_admin_or_active_status(): void
    {
        $admin = User::factory()->create(['admin' => true, 'active' => true]);

        $this->actingAs($admin)->put("/admin/users/{$admin->id}", [
            'admin' => 0,
            'active' => 0,
        ]);

        $admin->refresh();
        $this->assertTrue($admin->admin);
        $this->assertTrue($admin->active);
    }

    #[Test]
    public function inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'active' => false,
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
