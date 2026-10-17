<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PasswordResetFlowTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Email', false);
    }

    #[Test]
    public function unknown_email_gets_same_response_as_known(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $known->assertRedirect();
        $unknown->assertRedirect();
        $this->assertSame(
            $known->getSession()->get('status'),
            $unknown->getSession()->get('status')
        );
        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function valid_token_resets_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->assertNotNull($token);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 's3cure-new-pass',
            'password_confirmation' => 's3cure-new-pass',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertTrue(Password::createToken($user) !== $token);
    }
}
