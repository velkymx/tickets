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
        $hasV1Prefix = false;

        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                $hasV1Prefix = true;
                break;
            }
        }

        $this->assertTrue($hasV1Prefix);
    }
}
