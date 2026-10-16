<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SsoFlowTest extends TestCase
{
    use SeedsDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.github', [
            'client_id' => 'test-id',
            'client_secret' => 'test-secret',
            'redirect' => 'http://localhost/auth/github/callback',
        ]);
    }

    private function mockSocialite(string $id = '99', string $email = 'sso@example.com', string $name = 'Sso User'): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn($id);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        $driver = Mockery::mock();
        $driver->shouldReceive('redirect')->andReturn(redirect('https://github.com/login'));
        $driver->shouldReceive('user')->andReturn($socialUser);
        $driver->shouldReceive('getToken')->andReturn('mock-token');
        $driver->token = 'mock-token';

        Socialite::shouldReceive('driver')->with('github')->andReturn($driver);
    }

    #[Test]
    public function unconfigured_provider_is_not_found(): void
    {
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/twitter/redirect')->assertNotFound();
    }

    #[Test]
    public function linked_account_signs_in(): void
    {
        $this->mockSocialite(id: '42', email: 'linked@example.com');
        $user = User::factory()->create(['email' => 'linked@example.com']);
        SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'github', 'provider_id' => '42']);

        $this->get('/auth/github/callback')->assertRedirect('/home');
        $this->assertAuthenticatedAs($user->fresh());
    }

    #[Test]
    public function unknown_user_without_allow_list_is_refused(): void
    {
        $this->mockSocialite(email: 'stranger@example.com');

        Http::fake(['https://api.github.com/user/emails' => Http::response([
            ['email' => 'stranger@example.com', 'verified' => true, 'primary' => true],
        ])]);

        $response = $this->get('/auth/github/callback');

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
        $this->assertGuest();
    }

    #[Test]
    public function allowed_domain_auto_creates_user(): void
    {
        putenv('SSO_ALLOWED_DOMAINS=example.com');
        $this->mockSocialite(email: 'newbie@example.com');

        Http::fake(['https://api.github.com/user/emails' => Http::response([
            ['email' => 'newbie@example.com', 'verified' => true, 'primary' => true],
        ])]);

        $this->get('/auth/github/callback')->assertRedirect('/home');

        $user = User::where('email', 'newbie@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '99',
        ]);

        putenv('SSO_ALLOWED_DOMAINS');
    }

    #[Test]
    public function deactivated_user_is_blocked(): void
    {
        $this->mockSocialite(id: '77', email: 'gone@example.com');
        $user = User::factory()->create(['email' => 'gone@example.com', 'active' => false]);
        SocialAccount::factory()->create(['user_id' => $user->id, 'provider' => 'github', 'provider_id' => '77']);

        $this->get('/auth/github/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
