<?php

namespace App\SystemHealth\Checks;

use App\Models\Automation;
use App\Models\KbArticle;
use App\Models\User;
use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;

class FeaturesCheck implements HealthCheck
{
    public const PROVIDERS = [
        'github' => 'GitHub',
        'google' => 'Google',
        'gitlab' => 'GitLab',
        'azure' => 'Microsoft',
    ];

    public function section(): string
    {
        return 'Features';
    }

    public function run(): array
    {
        $results = [
            $this->flag('Password login', (bool) config('auth.password_login_enabled')),
            $this->flag('Registration', (bool) config('auth.registration_enabled')),
            ...$this->sso(),
            $this->mail(),
            $this->queue(),
            $this->digests(),
            $this->automations(),
            $this->api(),
            $this->knowledgeBase(),
            $this->cacheAndSession(),
            $this->flag('Debug mode', (bool) config('app.debug')),
            $this->flag('Maintenance mode', app()->maintenanceMode()->active()),
        ];

        if (! (bool) config('auth.password_login_enabled') && empty($this->configuredProviders())) {
            $results[] = new HealthResult(
                HealthStatus::Problem,
                'Sign-in',
                'no way to sign in',
                'Enable password login or configure an SSO provider'
            );
        }

        return $results;
    }

    private function flag(string $label, bool $on): HealthResult
    {
        return new HealthResult($on ? HealthStatus::Enabled : HealthStatus::Disabled, $label, $on ? 'on' : 'off');
    }

    /**
     * @return list<HealthResult>
     */
    private function sso(): array
    {
        $providers = $this->configuredProviders();

        if ($providers === []) {
            return [new HealthResult(HealthStatus::Disabled, 'SSO', 'disabled')];
        }

        $results = [];

        foreach (self::PROVIDERS as $key => $name) {
            $clientId = config("services.{$key}.client_id");

            if (! $clientId) {
                continue;
            }

            $secret = config("services.{$key}.client_secret");
            $redirect = config("services.{$key}.redirect");

            if (! $secret || ! $redirect) {
                $results[] = new HealthResult(
                    HealthStatus::Problem,
                    "SSO: {$name}",
                    'incomplete config',
                    "Set the client secret and redirect URI for {$name}"
                );

                continue;
            }

            $value = 'client ID set';

            if ($key === 'gitlab' && config('services.gitlab.host')) {
                $value .= ' ('.config('services.gitlab.host').')';
            }

            $results[] = new HealthResult(HealthStatus::Enabled, "SSO: {$name}", $value);
        }

        return $results;
    }

    /**
     * @return list<string>
     */
    private function configuredProviders(): array
    {
        return array_keys(array_filter(
            self::PROVIDERS,
            fn ($name, $key) => (bool) config("services.{$key}.client_id"),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    private function mail(): HealthResult
    {
        $mailer = (string) config('mail.default');
        $enabled = ! in_array($mailer, ['log', 'array'], true);

        return new HealthResult(
            $enabled ? HealthStatus::Enabled : HealthStatus::Disabled,
            'Mail',
            $enabled ? "{$mailer} (".config('mail.from.address').')' : "{$mailer} (nowhere to go)"
        );
    }

    private function queue(): HealthResult
    {
        $connection = (string) config('queue.default');

        return new HealthResult(
            HealthStatus::Enabled,
            'Queue',
            $connection === 'sync' ? 'sync (no worker)' : "{$connection} (async)"
        );
    }

    private function digests(): HealthResult
    {
        $mailer = (string) config('mail.default');
        $mailOn = ! in_array($mailer, ['log', 'array'], true);
        $async = config('queue.default') !== 'sync';
        $on = $mailOn && $async;

        return new HealthResult(
            $on ? HealthStatus::Enabled : HealthStatus::Disabled,
            'Email digests',
            $on ? 'on' : 'needs mail plus an async queue'
        );
    }

    private function automations(): HealthResult
    {
        $count = Automation::where('enabled', true)->count();

        if ($count === 0) {
            return new HealthResult(HealthStatus::Disabled, 'Automations', 'none enabled');
        }

        if (config('queue.default') === 'sync') {
            return new HealthResult(
                HealthStatus::Problem,
                'Automations',
                "{$count} enabled on a sync queue",
                'Use an async queue driver so webhooks do not block requests'
            );
        }

        return new HealthResult(HealthStatus::Enabled, 'Automations', "{$count} enabled");
    }

    private function api(): HealthResult
    {
        $tokens = User::whereNotNull('api_token')->count();

        return new HealthResult(HealthStatus::Enabled, 'REST API and MCP', "on ({$tokens} API tokens)");
    }

    private function knowledgeBase(): HealthResult
    {
        $count = KbArticle::where('visibility', 'public')->where('status', 'verified')->count();

        return new HealthResult(
            $count > 0 ? HealthStatus::Enabled : HealthStatus::Disabled,
            'Public knowledge base',
            "{$count} public articles"
        );
    }

    private function cacheAndSession(): HealthResult
    {
        return new HealthResult(
            HealthStatus::Enabled,
            'Cache and session',
            'cache: '.config('cache.default').', session: '.config('session.driver')
        );
    }
}
