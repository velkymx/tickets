<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class DeactivatedUserTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function deactivated_user_session_is_ended(): void
    {
        $user = User::factory()->create(['active' => false]);

        $this->actingAs($user)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
