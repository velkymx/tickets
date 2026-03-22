<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Tests\Traits\SeedsDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersAvatarMigrationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_adds_a_nullable_avatar_column_to_users(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'avatar'));

        $columns = collect(DB::select('PRAGMA table_info(users)'));
        $avatar = $columns->firstWhere('name', 'avatar');

        $this->assertNotNull($avatar);
        $this->assertSame(0, $avatar->notnull);
        $this->assertNull($avatar->dflt_value);
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
