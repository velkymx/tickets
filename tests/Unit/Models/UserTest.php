<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UserTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_generates_api_token(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->generateApiToken();

        $this->assertIsString($plainToken);
        $this->assertEquals(60, strlen($plainToken));
        $this->assertEquals(hash('sha256', $plainToken), $user->api_token);
    }

    #[Test]
    public function it_generates_unique_api_tokens(): void
    {
        $user = User::factory()->create();
        $token1 = $user->generateApiToken();
        $token2 = $user->generateApiToken();

        $this->assertNotEquals($token1, $token2);
    }

    #[Test]
    public function it_returns_a_storage_url_when_an_avatar_is_present(): void
    {
        $user = User::factory()->make([
            'avatar' => 'avatars/alice.png',
        ]);

        $this->assertStringEndsWith('/avatars/alice.png', $user->avatarUrl());
    }

    #[Test]
    public function it_returns_a_gravatar_url_when_avatar_is_missing(): void
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'avatar' => null,
        ]);

        $expected = 'https://www.gravatar.com/avatar/'.md5('test@example.com').'?s=46&d=mp';

        $this->assertSame($expected, $user->avatarUrl());
    }
}
