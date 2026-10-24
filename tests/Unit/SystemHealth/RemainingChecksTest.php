<?php

namespace Tests\Unit\SystemHealth;

use App\SystemHealth\Checks\CacheCheck;
use App\SystemHealth\Checks\DatabaseCheck;
use App\SystemHealth\Checks\MailCheck;
use App\SystemHealth\Checks\SecurityCheck;
use App\SystemHealth\Checks\StorageCheck;
use App\SystemHealth\HealthStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class RemainingChecksTest extends TestCase
{
    use SeedsDatabase;

    private function byLabel(array $results): array
    {
        $byLabel = [];

        foreach ($results as $result) {
            $byLabel[$result->label] = $result;
        }

        return $byLabel;
    }

    #[Test]
    public function log_mailer_outside_local_is_a_warning(): void
    {
        config(['mail.default' => 'log', 'app.env' => 'production']);

        $this->assertSame(HealthStatus::Warning, $this->byLabel((new MailCheck)->run())['Mailer']->status);
    }

    #[Test]
    public function real_mailer_is_ok(): void
    {
        config(['mail.default' => 'smtp']);

        $this->assertSame(HealthStatus::Ok, $this->byLabel((new MailCheck)->run())['Mailer']->status);
    }

    #[Test]
    public function cache_round_trip_is_ok(): void
    {
        $this->assertSame(HealthStatus::Ok, $this->byLabel((new CacheCheck)->run())['Cache read and write']->status);
    }

    #[Test]
    public function debug_outside_local_is_a_problem(): void
    {
        config(['app.debug' => true, 'app.env' => 'production']);

        $result = $this->byLabel((new SecurityCheck)->run())['Debug mode'];

        $this->assertSame(HealthStatus::Problem, $result->status);
        $this->assertNotNull($result->fix);
    }

    #[Test]
    public function empty_app_key_is_a_problem(): void
    {
        config(['app.key' => '']);

        $this->assertSame(HealthStatus::Problem, $this->byLabel((new SecurityCheck)->run())['App key']->status);
    }

    #[Test]
    public function mismatched_app_url_is_a_warning(): void
    {
        config(['app.url' => 'http://example.com']);

        $this->assertSame(HealthStatus::Warning, $this->byLabel((new SecurityCheck)->run())['App URL']->status);
    }

    #[Test]
    public function storage_reports_writability_and_free_space(): void
    {
        $results = $this->byLabel((new StorageCheck)->run());

        $this->assertSame(HealthStatus::Ok, $results['storage/']->status);
        $this->assertStringContainsString('GB free', $results['Free disk space']->value);
    }

    #[Test]
    public function database_connection_and_migrations_are_ok(): void
    {
        $results = $this->byLabel((new DatabaseCheck)->run());

        $this->assertSame(HealthStatus::Ok, $results['Connection']->status);
        $this->assertSame(HealthStatus::Ok, $results['Migrations']->status);
    }
}
