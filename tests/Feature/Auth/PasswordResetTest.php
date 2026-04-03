<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class PasswordResetTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function password_reset_tokens_table_matches_auth_config(): void
    {
        $configTable = config('auth.passwords.users.table');
        $user = User::factory()->create();

        // Insert a token into the configured table — will throw if table doesn't exist
        DB::table($configTable)->insert([
            'email' => $user->email,
            'token' => 'test-token',
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas($configTable, ['email' => $user->email]);
    }
}
