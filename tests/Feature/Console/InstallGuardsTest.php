<?php

namespace Tests\Feature\Console;

use App\Console\Commands\InstallCommand;
use App\Models\User;
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class InstallGuardsTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function env_line_quotes_hashes_and_spaces(): void
    {
        $this->assertSame('DB_PASSWORD="p#ssword"', InstallCommand::envLine('DB_PASSWORD', 'p#ssword'));
        $this->assertSame('DB_PASSWORD="p#ss word"', InstallCommand::envLine('DB_PASSWORD', 'p#ss word'));
    }

    #[Test]
    public function env_line_escapes_quotes_and_dollars(): void
    {
        $this->assertSame('DB_PASSWORD="say \\"hi\\" \\$x"', InstallCommand::envLine('DB_PASSWORD', 'say "hi" $x'));
    }

    #[Test]
    public function env_line_refuses_newline_injection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        InstallCommand::envLine('DB_PASSWORD', "p#ss word\nDB_ROOT_PASSWORD=hacked");
    }

    #[Test]
    public function env_line_leaves_plain_values_bare(): void
    {
        $this->assertSame('APP_URL=http://localhost:8000', InstallCommand::envLine('APP_URL', 'http://localhost:8000'));
        $this->assertSame('DB_PASSWORD=', InstallCommand::envLine('DB_PASSWORD', ''));
    }

    #[Test]
    public function php_gate_matches_composer_floor(): void
    {
        // Intent: the hardcoded gate string must track the composer floor.
        // Reads source as text; a comment containing the version would also
        // satisfy it, so treat this as a drift tripwire, not a behavior pin.
        $source = file_get_contents(app_path('Console/Commands/InstallCommand.php'));
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

        $this->assertSame('^8.4', $composer['require']['php']);
        $this->assertStringContainsString('8.4.0', (string) $source);
    }

    #[Test]
    public function quoted_env_values_round_trip_through_dotenv(): void
    {
        $values = [
            'simplevalue123',
            'my secret pass',
            'p#ssword',
            'say"hi',
            'pa$$w0rd',
            'back\slash',
            'a b#c d',
            'YWJjZGVmZ2hpamtsbW5vcA==',
        ];

        foreach ($values as $value) {
            $line = InstallCommand::envLine('DB_PASSWORD', $value);
            $parsed = Dotenv::parse($line);

            $this->assertSame($value, $parsed['DB_PASSWORD'] ?? null, "round-trip failed for [{$value}]");
        }
    }

    #[Test]
    public function node_gate_matches_package_engines(): void
    {
        $source = file_get_contents(app_path('Console/Commands/InstallCommand.php'));
        $package = json_decode((string) file_get_contents(base_path('package.json')), true);

        $this->assertSame('>=24', $package['engines']['node'] ?? null);
        $this->assertStringContainsString('24.0.0', (string) $source);
    }

    #[Test]
    public function auto_admin_refuses_missing_password(): void
    {
        $admin = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        InstallCommand::resolveAutoAdminCredentials($admin, 'Boss', 'boss@example.com', '');
    }

    #[Test]
    public function auto_admin_refuses_bad_email(): void
    {
        $admin = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        InstallCommand::resolveAutoAdminCredentials($admin, 'Boss', 'not-an-email', 's3cure-secret-pass');
    }

    #[Test]
    public function auto_admin_accepts_valid_credentials(): void
    {
        $admin = User::factory()->create();

        $this->assertSame(
            ['Boss', 'boss@example.com', 's3cure-secret-pass'],
            InstallCommand::resolveAutoAdminCredentials($admin, 'Boss', 'boss@example.com', 's3cure-secret-pass')
        );
    }
}
