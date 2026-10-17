<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ExecuteAutomationRuleJob;
use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ExecuteAutomationRuleJobTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function failing_action_is_logged_and_rethrown(): void
    {
        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        $rule = AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);
        $action = AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'config_json' => ['url' => 'https://93.184.215.14/hook', 'method' => 'TRACE', 'headers' => [], 'payload' => []],
        ]);
        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'automation_rule_id' => $rule->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'queued',
            'context_json' => [],
        ]);

        try {
            (new ExecuteAutomationRuleJob($run->id, $rule->id, []))->handle();
            $this->fail('Expected the action exception to be rethrown so the queue retries it.');
        } catch (\InvalidArgumentException) {
        }

        $this->assertDatabaseHas('automation_action_logs', [
            'automation_run_id' => $run->id,
            'automation_action_id' => $action->id,
            'status' => 'failed',
        ]);
        $this->assertSame('running', $run->fresh()->status);
    }

    #[Test]
    public function failed_hook_marks_run_failed(): void
    {
        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        $rule = AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);
        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'automation_rule_id' => $rule->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'running',
            'context_json' => [],
        ]);

        (new ExecuteAutomationRuleJob($run->id, $rule->id, []))->failed(new \RuntimeException('down'));

        $this->assertSame('failed', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->finished_at);
    }

    #[Test]
    public function five_hundred_response_marks_run_failed(): void
    {
        Http::fake(['https://93.184.215.14/*' => Http::response('error', 500)]);
        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        $rule = AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);
        AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'config_json' => ['url' => 'https://93.184.215.14/hook', 'method' => 'POST', 'headers' => [], 'payload' => []],
        ]);
        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'automation_rule_id' => $rule->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'queued',
            'context_json' => [],
        ]);

        (new ExecuteAutomationRuleJob($run->id, $rule->id, []))->handle();

        $this->assertDatabaseHas('automation_action_logs', [
            'automation_run_id' => $run->id,
            'status' => 'failed',
            'response_code' => 500,
        ]);
        $this->assertSame('failed', $run->fresh()->status);
    }
}
