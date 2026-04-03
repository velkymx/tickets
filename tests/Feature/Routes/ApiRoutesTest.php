<?php

namespace Tests\Feature\Routes;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    #[Test]
    public function it_applies_api_token_middleware_to_all_api_routes(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            if (! str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $this->assertContains('api.token', $route->middleware(), "Route {$route->uri()} should have api.token middleware");
        }
    }

    #[Test]
    public function it_prefixes_all_routes_with_v1(): void
    {
        $routes = Route::getRoutes();
        $apiRoutes = [];

        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $apiRoutes[] = $route->uri();
                $this->assertStringStartsWith('api/v1/', $route->uri(), "API route {$route->uri()} should be prefixed with api/v1/");
            }
        }

        $this->assertNotEmpty($apiRoutes, 'Expected at least one API route to exist');
    }
}
