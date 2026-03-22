<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_have_an_avatar()
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/1.png',
        ]);

        $this->assertEquals('avatars/1.png', $user->avatar);
    }

    /** @test */
    public function it_returns_gravatar_url_when_no_avatar_is_set()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'avatar' => null,
        ]);

        $expected = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?s=46&d=mp';
        $this->assertEquals($expected, $user->avatarUrl(46));
    }
}
