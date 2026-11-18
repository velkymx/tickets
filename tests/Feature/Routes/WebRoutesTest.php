<?php

namespace Tests\Feature\Routes;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    /**
     * Routes that legitimately have no name. Everything else must be named so
     * views and redirects can use route().
     */
    private const UNNAMED_ALLOWED = [
        '/',
        'broadcasting/auth',
        'login',
        'register',
        'mcp/tickets',
        'up',
    ];

    #[Test]
    public function every_app_route_has_a_name(): void
    {
        $unnamed = [];

        foreach (Route::getRoutes() as $route) {
            if ($this->isExemptFromNaming($route->uri())) {
                continue;
            }

            if (empty($route->getName())) {
                $unnamed[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $unnamed, 'Routes without names: '.implode(', ', $unnamed));
    }

    #[Test]
    #[DataProvider('stateChangingRoutes')]
    public function state_changing_routes_use_their_expected_verb(string $uri, string $verb): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->uri() === $uri && in_array($verb, $route->methods(), true)
        );

        $this->assertNotNull($route, "Route {$uri} should accept {$verb}");
    }

    #[Test]
    public function mutating_routes_never_accept_get(): void
    {
        foreach (Route::getRoutes() as $route) {
            if (! preg_match('/(update|batch|claim|watch)\b/', $route->uri())) {
                continue;
            }

            $this->assertNotContains(
                'GET',
                $route->methods(),
                "Route {$route->uri()} mutates state and must not accept GET"
            );
        }
    }

    public static function stateChangingRoutes(): array
    {
        return [
            'batch update' => ['tickets/batch', 'POST'],
            'import' => ['tickets/import', 'POST'],
            'claim' => ['tickets/claim/{id}', 'POST'],
            'watch ticket' => ['tickets/watch/{id}', 'POST'],
            'update ticket' => ['tickets/update/{id}', 'PUT'],
            'update milestone' => ['milestone/update/{id}', 'PUT'],
            'edit release' => ['release/edit/{id}', 'PUT'],
            'store release' => ['release/store', 'POST'],
            'create note' => ['notes', 'POST'],
            'create article' => ['kb', 'POST'],
            'update article' => ['kb/{slug}', 'PUT'],
            'create api ticket' => ['api/v1/tickets', 'POST'],
            'update api ticket' => ['api/v1/tickets/{id}', 'PUT'],
            'update user access' => ['admin/users/{user}', 'PUT'],
        ];
    }

    private function isExemptFromNaming(string $uri): bool
    {
        return in_array($uri, self::UNNAMED_ALLOWED, true)
            || str_starts_with($uri, '_ignition/')
            || str_starts_with($uri, 'storage/');
    }
}
