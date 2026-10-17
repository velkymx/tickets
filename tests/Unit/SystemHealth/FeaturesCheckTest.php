<?php

namespace Tests\Unit\SystemHealth;

use App\Models\Automation;
use App\SystemHealth\Checks\FeaturesCheck;
use App\SystemHealth\HealthStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class FeaturesCheckTest extends TestCase
{
    use SeedsDatabase;

    private function results(): array
    {
        $byLabel = [];

        foreach ((new FeaturesCheck)->run() as $result) {
            $byLabel[$result->label] = $result;
        }

        return $byLabel;
    }

    public static function toggles(): array
    {
        return [
            'password login' => ['auth.password_login_enabled', 'Password login'],
            'registration' => ['auth.registration_enabled', 'Registration'],
        ];
    }

    #[Test]
    #[DataProvider('toggles')]
    public function feature_flags_render_on_and_off(string $key, string $label): void
    {
        config([$key => true]);
        $this->assertSame(HealthStatus::Enabled, $this->results()[$label]->status);

        config([$key => false]);
        $this->assertSame(HealthStatus::Disabled, $this->results()[$label]->status);
    }

    #[Test]
    public function sso_shows_one_row_per_configured_provider(): void
    {
        config([
            'services.github.client_id' => 'id',
            'services.github.client_secret' => 'secret',
            'services.github.redirect' => 'https://app/callback',
        ]);

        $results = $this->results();

        $this->assertSame(HealthStatus::Enabled, $results['SSO: GitHub']->status);
        $this->assertArrayNotHasKey('SSO: Google', $results);
    }

    #[Test]
    public function sso_disabled_without_any_provider(): void
    {
        $results = $this->results();

        $this->assertSame(HealthStatus::Disabled, $results['SSO']->status);
    }

    #[Test]
    public function sso_client_id_without_secret_is_a_problem(): void
    {
        config(['services.google.client_id' => 'id']);

        $result = $this->results()['SSO: Google'];

        $this->assertSame(HealthStatus::Problem, $result->status);
        $this->assertNotNull($result->fix);
    }

    #[Test]
    public function no_sign_in_when_password_off_and_no_sso(): void
    {
        config(['auth.password_login_enabled' => false]);

        $result = $this->results()['Sign-in'];

        $this->assertSame(HealthStatus::Problem, $result->status);
    }

    #[Test]
    public function sync_queue_downgrades_enabled_automations(): void
    {
        Automation::factory()->create(['enabled' => true]);
        config(['queue.default' => 'sync']);

        $this->assertSame(HealthStatus::Problem, $this->results()['Automations']->status);
    }

    #[Test]
    public function mail_shows_mailer_and_from_address(): void
    {
        config(['mail.default' => 'smtp']);

        $result = $this->results()['Mail'];

        $this->assertSame(HealthStatus::Enabled, $result->status);
        $this->assertStringContainsString('smtp', $result->value);
    }
}
