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
    public function avatar_component_uses_the_model_hash(): void
    {
        $blade = file_get_contents(resource_path('views/components/avatar.blade.php'));

        $this->assertStringNotContainsString('md5(', $blade);
        $this->assertStringContainsString('gravatar_hash', $blade);
    }

    #[Test]
    public function avatar_component_renders_gravatar_image(): void
    {
        $user = User::factory()->make(['email' => 'test@example.com']);

        $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

        $this->assertStringContainsString(md5('test@example.com'), $html);
        $this->assertStringContainsString('gravatar.com/avatar/', $html);
    }
}
