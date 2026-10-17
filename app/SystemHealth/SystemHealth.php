<?php

namespace App\SystemHealth;

class SystemHealth
{
    /**
     * @var list<class-string<HealthCheck>>
     */
    private array $checks = [
        Checks\SystemInfoCheck::class,
        Checks\RequirementsCheck::class,
        Checks\FeaturesCheck::class,
    ];

    /**
     * @return array<string, list<HealthResult>>
     */
    public function report(): array
    {
        $report = [];

        foreach ($this->checks as $check) {
            $instance = app($check);

            try {
                $report[$instance->section()] = $instance->run();
            } catch (\Throwable $e) {
                $report[$instance->section()] = [
                    new HealthResult(HealthStatus::Error, $instance->section(), $e->getMessage()),
                ];
            }
        }

        return $report;
    }

    /**
     * @param  array<string, list<HealthResult>>  $report
     */
    public function summary(array $report): array
    {
        $problems = 0;
        $warnings = 0;

        foreach ($report as $results) {
            foreach ($results as $result) {
                if ($result->status === HealthStatus::Problem || $result->status === HealthStatus::Error) {
                    $problems++;
                } elseif ($result->status === HealthStatus::Warning) {
                    $warnings++;
                }
            }
        }

        return ['problems' => $problems, 'warnings' => $warnings];
    }
}
