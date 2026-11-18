<?php

namespace App\SystemHealth\Checks;

use App\SystemHealth\HealthCheck;
use App\SystemHealth\HealthResult;
use App\SystemHealth\HealthStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueCheck implements HealthCheck
{
    public function section(): string
    {
        return 'Queue';
    }

    public function run(): array
    {
        $connection = (string) config('queue.default');
        $results = [];

        if ($connection === 'sync') {
            $results[] = new HealthResult(
                HealthStatus::Warning,
                'Worker',
                'sync driver runs jobs inside web requests',
                'Use an async queue driver with php artisan queue:work'
            );

            return [...$results, ...$this->failedJobs()];
        }

        if ($connection === 'database') {
            $table = (string) config('queue.connections.database.table', 'jobs');

            if (! Schema::hasTable($table)) {
                return [new HealthResult(
                    HealthStatus::Problem,
                    'Queue backend',
                    "table [{$table}] is missing",
                    'Run php artisan migrate to create the jobs table'
                )];
            }

            $results[] = new HealthResult(HealthStatus::Ok, 'Queue backend', "database ({$table})");
            $results[] = $this->staleJob($table);
        } else {
            $results[] = new HealthResult(HealthStatus::Ok, 'Queue backend', $connection);
        }

        return [...$results, ...$this->failedJobs()];
    }

    private function staleJob(string $table): HealthResult
    {
        $oldest = DB::table($table)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', now()->timestamp)
            ->min('available_at');

        if ($oldest !== null && now()->timestamp - (int) $oldest > 300) {
            return new HealthResult(
                HealthStatus::Warning,
                'Pending jobs',
                'oldest job waiting over 5 minutes',
                'Run php artisan queue:work'
            );
        }

        return new HealthResult(HealthStatus::Ok, 'Pending jobs', 'flowing');
    }

    /**
     * @return list<HealthResult>
     */
    private function failedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        $failed = DB::table('failed_jobs')->count();

        if ($failed === 0) {
            return [new HealthResult(HealthStatus::Ok, 'Failed jobs', 'none')];
        }

        $newest = DB::table('failed_jobs')->max('failed_at');

        return [new HealthResult(
            HealthStatus::Warning,
            'Failed jobs',
            "{$failed} failed".($newest ? ", newest {$newest}" : ''),
            'Inspect them with php artisan queue:failed'
        )];
    }
}
