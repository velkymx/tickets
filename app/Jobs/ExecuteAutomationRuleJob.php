<?php

namespace App\Jobs;

use App\Automations\Contracts\AutomationActionDriverInterface;
use App\Automations\Drivers\WebhookActionDriver;
use App\Models\AutomationActionLog;
use App\Models\AutomationRule;
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

        $rule = AutomationRule::with('actions')->findOrFail($this->automationRuleId);

        foreach ($rule->actions as $action) {
            $driver = $this->resolveDriver($action->type);

            try {
                $driver->execute($action, $this->context, $run);
            } catch (\Throwable $e) {
                AutomationActionLog::create([
                    'automation_run_id' => $run->id,
                    'automation_action_id' => $action->id,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'attempts' => $this->attempts(),
                    'executed_at' => now(),
                ]);
                throw $e;
            }
        }

        $run->update(['status' => 'success', 'finished_at' => now()]);
    }

    private function resolveDriver(string $type): AutomationActionDriverInterface
    {
        return match ($type) {
            'call_webhook', 'call_api' => app(WebhookActionDriver::class),
            default => throw new \RuntimeException("Unknown action driver: {$type}"),
        };
    }
}
