<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class UsersAvatarMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_a_nullable_avatar_column_to_users(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'avatar'));

        $columns = collect(Schema::getColumns('users'));
        $avatar = $columns->firstWhere('name', 'avatar');

        $this->assertNotNull($avatar);
        $this->assertTrue($avatar['nullable']);
        $this->assertTrue($avatar['default'] === null || strtoupper((string) $avatar['default']) === 'NULL');
    }

    #[Test]
    public function it_persists_avatar_paths_for_users(): void
    {
        $user = User::factory()->create([
            'avatar' => 'avatars/alice.png',
        ]);

        $this->assertSame('avatars/alice.png', $user->fresh()->avatar);
    }
}
