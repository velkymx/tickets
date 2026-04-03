<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AuthenticateApiTokenTest extends TestCase
{
    use SeedsDatabase;

    protected AuthenticateApiToken $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthenticateApiToken;
    }

    protected function makeRequest(?string $token = null): Request
    {
        $request = Request::create('/api/test', 'GET');
        if ($token) {
            $request->headers->set('Authorization', 'Bearer '.$token);
        }

        return $request;
    }

    protected function handle($request)
    {
        return $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
    }

    #[Test]
    public function it_rejects_request_without_bearer_token(): void
    {
        $request = $this->makeRequest();
        $response = $this->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function it_rejects_token_shorter_than_32_characters(): void
    {
        $request = $this->makeRequest(str_repeat('a', 31));
        $response = $this->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid token format', $response->getContent());
    }

    #[Test]
    public function it_rejects_token_longer_than_128_characters(): void
    {
        $request = $this->makeRequest(str_repeat('a', 129));
        $response = $this->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid token', $response->getContent());
    }

    #[Test]
    public function it_rejects_invalid_token(): void
    {
        $request = $this->makeRequest(str_repeat('a', 32));
        $response = $this->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid token', $response->getContent());
    }

    #[Test]
    public function it_authenticates_valid_token(): void
    {
        $plainToken = str_repeat('a', 32);
        $hashedToken = hash('sha256', $plainToken);

        $user = User::factory()->create(['api_token' => $hashedToken]);

        $request = $this->makeRequest($plainToken);
        $response = $this->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($user->id, $request->attributes->get('api_user')->id);
    }

    #[Test]
    public function it_hashes_token_with_sha256_before_lookup(): void
    {
        $plainToken = str_repeat('b', 40);
        $hashedToken = hash('sha256', $plainToken);

        $user = User::factory()->create(['api_token' => $hashedToken]);

        $request = $this->makeRequest($plainToken);
        $response = $this->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_passes_request_to_next_middleware_on_success(): void
    {
        $plainToken = str_repeat('c', 32);
        $hashedToken = hash('sha256', $plainToken);

        User::factory()->create(['api_token' => $hashedToken]);

        $request = $this->makeRequest($plainToken);
        $response = $this->handle($request);

        $this->assertEquals('OK', $response->getContent());
    }
}
