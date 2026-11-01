<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class SsoLinkingTest extends TestCase
{
    use SeedsDatabase;

    private function fakeAzure(string $email, string $tid): void
    {
        config(['services.azure.client_id' => 'id', 'services.sso.azure_tenant' => 'good-tenant']);

        $socialUser = (new SocialiteUser)->setRaw(['tid' => $tid])->map([
            'id' => 'azure-1', 'email' => $email, 'name' => 'Attacker',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($socialUser);
    }

    #[Test]
    public function azure_email_match_does_not_take_over_an_existing_account(): void
    {
        $admin = User::factory()->create(['email' => 'boss@corp.test', 'admin' => true]);
        $this->fakeAzure('boss@corp.test', 'evil-tenant');

        $this->get('/auth/azure/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(0, $admin->socialAccounts()->count());
    }

    #[Test]
    public function register_gate_reads_config_not_env(): void
    {
        config(['services.sso.allowed_domains' => 'corp.test']);
        $this->fakeAzure('new@corp.test', 'good-tenant');

        $this->get('/auth/azure/callback')->assertRedirect('/home');
        $this->assertAuthenticated();
    }
}
