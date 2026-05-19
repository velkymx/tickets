<?php

namespace App\Jobs;

use App\Models\AutomationRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteAutomationRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly int $automationRunId,
        public readonly int $automationRuleId,
        public readonly array $context,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $run = AutomationRun::findOrFail($this->automationRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        // Action execution will be added in Task 8
        $run->update(['status' => 'success', 'finished_at' => now()]);
    }
}
