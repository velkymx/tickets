<?php

namespace Tests\Feature\Routing;

use App\Http\Controllers\TicketsController;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeadUploadRouteTest extends TestCase
{
    #[Test]
    public function upload_route_is_gone(): void
    {
        $this->assertFalse(Route::has('tickets.upload'));
    }

    #[Test]
    public function upload_action_is_gone(): void
    {
        $this->assertFalse(method_exists(TicketsController::class, 'upload'));
    }

    #[Test]
    public function upload_path_is_not_routed(): void
    {
        $routes = collect(Route::getRoutes())->filter(
            fn ($route) => in_array('/tickets/upload', (array) $route->uri())
        );

        $this->assertTrue($routes->isEmpty());
    }
}
