<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use Illuminate\Support\Facades\DB;

class DatabaseCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Database';
    }

    public function run(): array
    {
        try {
            DB::select('select 1');

            $results = [new HealthResult(HealthStatus::Ok, 'Connection', config('database.default').' (reachable)')];
        } catch (\Throwable $e) {
            return [new HealthResult(
                HealthStatus::Problem,
                'Connection',
                'failed: '.$e->getMessage(),
                'Check DB_CONNECTION and the database server'
            )];
        }

        $pending = $this->pendingMigrations();

        $results[] = $pending === []
            ? new HealthResult(HealthStatus::Ok, 'Migrations', 'up to date')
            : new HealthResult(
                HealthStatus::Problem,
                'Migrations',
                count($pending).' pending',
                'Run php artisan migrate'
            );

        return $results;
    }

    /**
     * @return list<string>
     */
    private function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = $migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }
}
