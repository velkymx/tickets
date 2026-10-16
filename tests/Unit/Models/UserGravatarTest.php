<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UserGravatarTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function gravatar_hash_lives_on_the_model_accessor(): void
    {
        $user = User::factory()->make(['email' => 'Test@Example.com']);

        $this->assertSame(md5('test@example.com'), $user->gravatar_hash);
    }

    #[Test]
    public function avatar_component_renders_gravatar_image(): void
    {
        $user = User::factory()->make(['email' => 'test@example.com']);

        $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

        $this->assertStringContainsString(md5('test@example.com'), $html);
        $this->assertStringContainsString('gravatar.com/avatar/', $html);
    }

    #[Test]
    public function avatar_component_prefers_uploaded_avatar(): void
    {
        $user = User::factory()->make([
            'email' => 'test@example.com',
            'avatar' => 'avatars/alice.png',
        ]);

        $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

        $this->assertStringContainsString('/avatars/alice.png', $html);
        $this->assertStringNotContainsString('gravatar.com/avatar/', $html);
    }
}
