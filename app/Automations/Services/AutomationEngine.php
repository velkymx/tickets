<?php

namespace App\Automations\Services;

use App\Automations\Evaluators\ConditionEvaluator;
use App\Jobs\ExecuteAutomationRuleJob;
use App\Models\Automation;
use App\Models\AutomationRun;

class AutomationEngine
{
    public function __construct(
        private readonly ConditionEvaluator $evaluator,
    ) {}

    public function process(string $trigger, array $context): void
    {
        $automations = Automation::query()
            ->with(['rules.actions'])
            ->where('trigger', $trigger)
            ->where('enabled', true)
            ->get();

        foreach ($automations as $automation) {
            $matched = false;

            foreach ($automation->rules as $rule) {
                if (! $this->evaluator->matches($rule->conditions_json, $context)) {
                    continue;
                }

                $run = AutomationRun::create([
                    'automation_id' => $automation->id,
                    'automation_rule_id' => $rule->id,
                    'trigger_name' => $trigger,
                    'status' => 'queued',
                    'context_json' => $context,
                ]);

                dispatch(new ExecuteAutomationRuleJob($run->id, $rule->id, $context));

                $matched = true;

                if (! $rule->continue_processing) {
                    break;
                }
            }

            if ($matched && $automation->stop_after_match) {
                break;
            }
        }
    }
}
