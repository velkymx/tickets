<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    private const PROVIDERS = ['github', 'google', 'gitlab', 'azure'];

    public static function enabledProviders(): array
    {
        return array_values(array_filter(
            self::PROVIDERS,
            fn ($provider) => (bool) config("services.{$provider}.client_id")
        ));
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureEnabled($provider);

        $driver = Socialite::driver($provider);

        if ($provider === 'gitlab' && config('services.gitlab.host')) {
            $driver->setHost(config('services.gitlab.host'));
        }

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureEnabled($provider);

        $driver = Socialite::driver($provider);

        if ($provider === 'gitlab' && config('services.gitlab.host')) {
            $driver->setHost(config('services.gitlab.host'));
        }

        $socialUser = $driver->user();

        $account = SocialAccount::where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($account) {
            return $this->signIn($account->user);
        }

        $email = strtolower(trim((string) $socialUser->getEmail()));
        $existing = $email !== '' ? User::where('email', $email)->first() : null;

        if ($existing) {
            if (! $this->verifiedEmail($provider, $driver, $socialUser, $email)
                || ! $this->passesProviderGate($provider, $driver, $socialUser)) {
                return redirect()->route('login')->withErrors([
                    'email' => 'An account with this email already exists. Sign in with your password or contact an administrator.',
                ]);
            }

            $existing->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => (string) $socialUser->getId(),
            ]);

            return $this->signIn($existing);
        }

        if (! $this->allowedToRegister($provider, $driver, $socialUser, $email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'This account is not allowed to sign in. Contact an administrator.',
            ]);
        }

        $user = User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $email,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
        ]);

        $user->forceFill(['active' => true])->save();

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
        ]);

        return $this->signIn($user);
    }

    private function signIn(?User $user): RedirectResponse
    {
        if (! $user || ! $user->active) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated. Contact an administrator.',
            ]);
        }

        Auth::login($user, true);
        session()->regenerate();

        return redirect()->intended('/home');
    }

    private function ensureEnabled(string $provider): void
    {
        abort_unless(in_array($provider, self::enabledProviders(), true), 404);
    }

    private function verifiedEmail(string $provider, mixed $driver, mixed $socialUser, string $email): bool
    {
        return match ($provider) {
            'google' => (bool) ($socialUser->user['email_verified'] ?? false),
            'github' => $this->githubVerifiedPrimary($driver, $email),
            'gitlab' => ! empty($socialUser->user['confirmed_at']),
            default => false,
        };
    }

    private function passesProviderGate(string $provider, mixed $driver, mixed $socialUser): bool
    {
        $tenant = (string) config('services.sso.azure_tenant');
        $org = (string) config('services.sso.github_org');
        $group = (string) config('services.sso.gitlab_group');

        if ($provider === 'azure' && $tenant !== '' && (string) ($socialUser->user['tid'] ?? '') !== $tenant) {
            return false;
        }

        if ($provider === 'github' && $org !== '' && ! $this->githubOrgMember($driver, $org)) {
            return false;
        }

        if ($provider === 'gitlab' && $group !== '' && ! $this->gitlabGroupMember($driver, $group)) {
            return false;
        }

        return true;
    }

    private function githubVerifiedPrimary(mixed $driver, string $email): bool
    {
        try {
            $response = Http::withToken($driver->token)->get('https://api.github.com/user/emails');

            if (! $response->successful()) {
                return false;
            }

            foreach ($response->json() as $entry) {
                if (strtolower((string) ($entry['email'] ?? '')) === $email
                    && ($entry['verified'] ?? false)
                    && ($entry['primary'] ?? false)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    private function allowedToRegister(string $provider, mixed $driver, mixed $socialUser, string $email): bool
    {
        if ($email === '') {
            return false;
        }

        $domains = array_filter(array_map(
            fn ($d) => strtolower(trim($d)),
            explode(',', (string) config('services.sso.allowed_domains'))
        ));

        if ($domains !== [] && ! in_array(Str::after($email, '@'), $domains, true)) {
            return false;
        }

        if (! $this->passesProviderGate($provider, $driver, $socialUser)) {
            return false;
        }

        // Fail closed: at least one gate must be configured.
        return $domains !== []
            || (string) config('services.sso.azure_tenant') !== ''
            || (string) config('services.sso.github_org') !== ''
            || (string) config('services.sso.gitlab_group') !== '';
    }

    private function githubOrgMember(mixed $driver, string $org): bool
    {
        try {
            return Http::withToken($driver->token)
                ->get("https://api.github.com/user/memberships/orgs/{$org}")
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function gitlabGroupMember(mixed $driver, string $group): bool
    {
        try {
            $host = rtrim((string) (config('services.gitlab.host') ?: 'https://gitlab.com'), '/');
            $response = Http::withToken($driver->token)->get("{$host}/api/v4/groups", [
                'search' => $group,
                'per_page' => 50,
            ]);

            if (! $response->successful()) {
                return false;
            }

            foreach ($response->json() as $entry) {
                if (strtolower((string) ($entry['full_path'] ?? '')) === strtolower($group)) {
                    return true;
                }
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
}
