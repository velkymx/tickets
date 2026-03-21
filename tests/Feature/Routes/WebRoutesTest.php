<?php

namespace Tests\Feature\Routes;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    #[Test]
    public function it_names_all_app_routes(): void
    {
        $routes = Route::getRoutes();
        $unnamed = [];

        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/')) {
                continue;
            }

            $appUris = ['ticket', 'milestone', 'project', 'release', 'note', 'user'];
            $isAppRoute = false;

            foreach ($appUris as $uri) {
                if (str_starts_with($route->uri(), $uri) || str_contains($route->uri(), $uri)) {
                    $isAppRoute = true;
                    break;
                }
            }

            if ($isAppRoute && empty($route->getName())) {
                $unnamed[] = $route->uri();
            }
        }

        $this->assertEmpty($unnamed, 'App routes without names: '.implode(', ', $unnamed));
    }

    #[Test]
    public function it_uses_post_for_state_changing_operations(): void
    {
        $routes = Route::getRoutes();
        $stateChangingUris = ['claim', 'watch', 'note', 'store', 'batch', 'upload', 'import'];

        foreach ($routes as $route) {
            $methods = $route->methods();

            if (in_array('GET', $methods) || in_array('HEAD', $methods)) {
                continue;
            }

            foreach ($stateChangingUris as $uri) {
                if (str_contains($route->uri(), $uri)) {
                    $this->assertContains('POST', $methods, "Route {$route->uri()} should use POST");
                }
            }
        }
    }
}
