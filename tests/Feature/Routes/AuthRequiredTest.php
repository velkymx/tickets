<?php

namespace Tests\Feature\Routes;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthRequiredTest extends TestCase
{
    /**
     * Routes that are deliberately reachable without a session or API token.
     * Adding a public route means adding it here.
     */
    private const PUBLIC_ROUTES = [
        '/',
        '_ignition/health-check',
        '_ignition/execute-solution',
        '_ignition/update-config',
        'api/docs',
        'api/openapi.yaml',
        'auth/{provider}/callback',
        'auth/{provider}/redirect',
        'broadcasting/auth',
        'forgot-password',
        'kb',
        'kb/{slug}',
        'kb/category/{slug}',
        'kb/search',
        'kb/tag/{slug}',
        'login',
        'mcp/tickets',
        'register',
        'reset-password',
        'reset-password/{token}',
        'storage/{path}',
        'up',
    ];

    #[Test]
    public function every_non_public_route_requires_auth(): void
    {
        $unprotected = [];

        foreach (Route::getRoutes() as $route) {
            if (in_array($route->uri(), self::PUBLIC_ROUTES, true)) {
                continue;
            }

            if (! $this->hasAuthMiddleware($route->middleware())) {
                $unprotected[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $unprotected, 'Routes without authentication: '.implode(', ', $unprotected));
    }

    #[Test]
    public function a_guest_is_redirected_from_a_web_page(): void
    {
        $this->get('/tickets')->assertRedirect('/login');
    }

    #[Test]
    public function a_guest_is_unauthorized_on_the_api(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    private function hasAuthMiddleware(array $middleware): bool
    {
        foreach ($middleware as $name) {
            if ($name === 'auth' || $name === 'api.token' || str_starts_with($name, 'auth:')) {
                return true;
            }
        }

        return false;
    }
}
