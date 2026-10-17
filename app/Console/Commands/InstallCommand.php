<?php

namespace App\Console\Commands;

use App\Models\User;
use App\SystemHealth\Requirements;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'app:install
        {--y|yes : Answer every question with the default}
        {--no-build : Skip npm install and asset build}
        {--no-seed : Skip database seeding}';

    protected $description = 'Guided setup: environment, database, migrations, seeds, and assets';

    public function handle(): int
    {
        $this->components->info('Tickets setup');

        $base = $this->checkBasePrerequisites();

        if ($base !== []) {
            $this->reportMissing($base);

            return self::FAILURE;
        }

        $auto = (bool) $this->option('yes');
        $build = ! $this->option('no-build');
        $seed = ! $this->option('no-seed');

        $appUrl = $this->answer('APP_URL', text('App URL', default: config('app.url') ?: 'http://localhost:8000'), $auto);
        $driver = $this->answer(
            'DB_CONNECTION',
            select('Database driver', ['sqlite' => 'SQLite (file, simplest)', 'mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL'], default: env('DB_CONNECTION', 'sqlite')),
            $auto
        );

        if (! in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            $this->components->error("Unsupported driver: {$driver}. Use sqlite, mysql, or pgsql.");

            return self::FAILURE;
        }

        $settings = ['APP_URL' => $appUrl, 'DB_CONNECTION' => $driver];

        if ($driver === 'sqlite') {
            $settings['DB_DATABASE'] = env('DB_DATABASE') ?: database_path('database.sqlite');
        } else {
            $portDefault = $driver === 'pgsql' ? '5432' : '3306';
            $settings['DB_HOST'] = $this->answer('DB_HOST', text('Database host', default: env('DB_HOST', '127.0.0.1')), $auto);
            $settings['DB_PORT'] = $this->answer('DB_PORT', text('Database port', default: (string) env('DB_PORT', $portDefault)), $auto);
            $settings['DB_DATABASE'] = $this->answer('DB_DATABASE', text('Database name', default: (string) env('DB_DATABASE', 'tickets')), $auto);
            $settings['DB_USERNAME'] = $this->answer('DB_USERNAME', text('Database username', default: (string) env('DB_USERNAME', 'root')), $auto);
            $settings['DB_PASSWORD'] = $this->answer('DB_PASSWORD', password('Database password', required: false) ?? '', $auto);
        }

        if ($this->option('no-seed')) {
            $seed = false;
        } elseif (! $auto) {
            $seed = confirm('Seed default data (statuses, admin account)', default: true);
        }

        if ($this->option('no-build')) {
            $build = false;
        } elseif (! $auto) {
            $build = confirm('Install npm packages and build assets', default: true);
        }

        $missing = $this->checkDriverPrerequisites($driver, $build);

        if ($missing !== []) {
            $this->reportMissing($missing);

            return self::FAILURE;
        }

        $this->writeEnv($settings);
        $this->components->twoColumnDetail('Environment', '.env updated');

        if ($driver === 'sqlite') {
            File::ensureDirectoryExists(dirname($settings['DB_DATABASE']));
            if (! File::exists($settings['DB_DATABASE'])) {
                File::put($settings['DB_DATABASE'], '');
            }
        }

        config(['database.default' => $driver]);
        foreach (['HOST', 'PORT', 'DATABASE', 'USERNAME', 'PASSWORD'] as $suffix) {
            if (isset($settings["DB_{$suffix}"])) {
                config(["database.connections.{$driver}.".strtolower($suffix) => $settings["DB_{$suffix}"]]);
            }
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->components->error('Could not connect to the database. Check the answers above and run again.');

            return self::FAILURE;
        }

        if (! File::exists(base_path('vendor/autoload.php'))) {
            if (! Process::run(['composer', '--version'])->successful()) {
                $this->components->error('Composer is required to install PHP dependencies.');

                return self::FAILURE;
            }
            if (! $this->runTask('Installing PHP dependencies', ['composer', 'install'])) {
                $this->components->error('Composer install failed. Fix the output above and run again.');

                return self::FAILURE;
            }
        }

        if (! preg_match('/^APP_KEY=.+$/m', File::get(base_path('.env')))) {
            $this->call('key:generate');
        } else {
            $this->components->twoColumnDetail('App key', 'already set, keeping it');
        }

        $this->call('migrate', ['--force' => true]);

        if (! is_link(public_path('storage'))) {
            $this->call('storage:link');
        } else {
            $this->components->twoColumnDetail('Storage link', 'already exists');
        }

        if ($seed) {
            $this->call('db:seed', ['--force' => true]);
        }

        $adminLogin = 'administrator / password123';

        if ($seed) {
            try {
                $adminLogin = $this->setupAdmin($auto) ?? $adminLogin;
            } catch (\RuntimeException $e) {
                $this->components->error($e->getMessage());

                return self::FAILURE;
            }
        }

        if ($build) {
            if (! Process::run(['node', '--version'])->successful() || ! Process::run(['npm', '--version'])->successful()) {
                $this->components->error('Node and npm are required for the asset build (or re-run with --no-build).');

                return self::FAILURE;
            }
            if (! File::isDirectory(base_path('node_modules'))) {
                if (! $this->runTask('Installing JS packages', ['npm', 'install'])) {
                    $this->components->error('npm install failed. Fix the output above and run again.');

                    return self::FAILURE;
                }
            }
            if (! $this->runTask('Building assets', ['npm', 'run', 'build'], 600)) {
                $this->components->error('Asset build failed. Fix the output above and run again.');

                return self::FAILURE;
            }
        }

        $this->components->info('Done. Start the app with php artisan serve, open '.$settings['APP_URL'].', and log in as '.$adminLogin.'.');

        return self::SUCCESS;
    }

    private function setupAdmin(bool $auto): ?string
    {
        $admin = User::where('email', 'support@ajbapps.com')->first()
            ?? User::where('admin', true)->first();

        if (! $admin) {
            $this->components->warn('No admin account found, skipping admin setup.');

            return null;
        }

        if (! $this->adminNeedsPasswordSetup($admin)) {
            $this->components->twoColumnDetail('Admin account', $admin->email.' (password already changed)');

            return $admin->name.' / '.$admin->email;
        }

        if ($auto) {
            [$name, $email, $secret] = self::resolveAutoAdminCredentials(
                $admin,
                (string) env('ADMIN_NAME', $admin->name),
                (string) env('ADMIN_EMAIL', $admin->email),
                (string) env('ADMIN_PASSWORD', '')
            );
        } else {
            if (! confirm('Set the admin login now (recommended)', default: true)) {
                $this->components->warn('Keeping the default admin password. Change it after first login.');

                return null;
            }

            $name = text('Admin name', default: $admin->name);
            $email = $this->askAdminEmail($admin);
            $secret = $this->askAdminPassword();
        }

        if ($secret === '') {
            $this->components->warn('Keeping the default admin password. Change it after first login.');

            return null;
        }

        $admin->update([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
        ]);

        $this->components->twoColumnDetail('Admin account', $email);

        return $name.' / '.$email;
    }

    private function askAdminEmail(User $admin): string
    {
        return text(
            'Admin email',
            default: $admin->email,
            validate: function ($value) use ($admin) {
                $result = Validator::make(['email' => $value], [
                    'email' => 'required|email|unique:users,email,'.$admin->id,
                ]);

                return $result->fails() ? $result->errors()->first('email') : null;
            }
        );
    }

    private function askAdminPassword(): string
    {
        while (true) {
            $first = password('Admin password (min 8 characters)', required: true) ?? '';

            if (mb_strlen($first) < 8) {
                $this->components->error('Password must be at least 8 characters.');

                continue;
            }

            $second = password('Confirm admin password', required: true) ?? '';

            if (! hash_equals($first, $second)) {
                $this->components->error('Passwords do not match.');

                continue;
            }

            return $first;
        }
    }

    private function adminNeedsPasswordSetup(User $admin): bool
    {
        return Hash::check('password123', $admin->password);
    }

    /**
     * Validate unattended admin credentials without prompting. Pure apart
     * from the uniqueness check, so tests call it directly. Throws when
     * the password is missing or the email is unusable.
     *
     * @return array{string, string, string} name, email, secret
     */
    public static function resolveAutoAdminCredentials(User $admin, string $name, string $email, string $secret): array
    {
        if ($secret === '') {
            throw new \RuntimeException('Refusing to seed with the default admin password in unattended mode. Set ADMIN_PASSWORD and run again.');
        }

        $failures = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email,'.$admin->id,
        ]);

        if ($failures->fails()) {
            throw new \RuntimeException('Invalid ADMIN_EMAIL: '.$failures->errors()->first('email'));
        }

        return [$name, $email, $secret];
    }

    private function checkBasePrerequisites(): array
    {
        return $this->missingFrom(Requirements::check());
    }

    private function checkDriverPrerequisites(string $driver, bool $build): array
    {
        return $this->missingFrom(Requirements::check($driver, $build));
    }

    /**
     * @param  list<RequirementResult>  $results
     */
    private function missingFrom(array $results): array
    {
        $missing = [];

        foreach ($results as $result) {
            if (! $result->ok) {
                $missing[] = [$result->name, $result->detail];
            }
        }

        return $missing;
    }

    private function reportMissing(array $missing): void
    {
        $this->components->error('Missing prerequisites:');

        foreach ($missing as [$need, $found]) {
            $this->components->twoColumnDetail($need, $found);
        }
    }

    private function runTask(string $label, array $command, int $timeout = 300): bool
    {
        $ok = false;

        $this->components->task($label, function () use ($command, $timeout, &$ok): bool {
            $ok = Process::timeout($timeout)->run($command)->successful();

            return $ok;
        });

        return $ok;
    }

    private function answer(string $envKey, mixed $prompted, bool $auto): string
    {
        $envValue = env($envKey);

        if ($auto) {
            return $envValue !== null && $envValue !== '' ? (string) $envValue : (string) $prompted;
        }

        return (string) $prompted;
    }

    /**
     * Render one KEY=value line that survives Dotenv parsing: values with
     * spaces, hashes, quotes, or dollar signs are double-quoted with
     * escaping, and newlines are refused instead of written.
     */
    public static function envLine(string $key, string $value): string
    {
        if (str_contains($value, "\n") || str_contains($value, "\r")) {
            throw new \InvalidArgumentException("Refusing to write {$key}: values cannot contain newlines.");
        }

        if ($value === '' || preg_match('/[\s#"\'$]/', $value) !== 1) {
            return $key.'='.$value;
        }

        return $key.'="'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    private function writeEnv(array $values): void
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            File::copy(base_path('.env.example'), $path);
        }

        $lines = explode("\n", File::get($path));

        foreach ($values as $key => $value) {
            $replacement = self::envLine($key, (string) $value);
            $matched = false;

            foreach ($lines as $i => $line) {
                if (str_starts_with($line, $key.'=')) {
                    $lines[$i] = $replacement;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                $lines[] = $replacement;
            }
        }

        File::put($path, implode("\n", $lines)."\n");
    }
}
