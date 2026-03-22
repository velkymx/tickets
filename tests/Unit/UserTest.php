<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\Traits\SeedsDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_can_have_an_avatar()
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/1.png',
        ]);

        $this->assertEquals('avatars/1.png', $user->avatar);
    }

    #[Test]
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
