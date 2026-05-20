<?php

namespace Tests\Feature\Automations;

use App\Automations\Services\AutomationEngine;
use App\Jobs\ExecuteAutomationRuleJob;
use App\Models\Automation;
use App\Models\AutomationRule;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationEngineTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_dispatches_job_for_matching_rule(): void
    {
        Queue::fake();

        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        $rule = AutomationRule::factory()->alwaysMatches()->create([
            'automation_id' => $automation->id,
        ]);

        app(AutomationEngine::class)->process('ticket.updated', ['ticket' => ['id' => 1], 'changes' => []]);

        Queue::assertPushed(ExecuteAutomationRuleJob::class, function ($job) use ($rule) {
            return $job->automationRuleId === $rule->id;
        });
    }

    #[Test]
    public function it_does_not_dispatch_for_disabled_automation(): void
    {
        Queue::fake();

        $automation = Automation::factory()->disabled()->forTrigger('ticket.updated')->create();
        AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);

        app(AutomationEngine::class)->process('ticket.updated', ['ticket' => ['id' => 1], 'changes' => []]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_stops_after_matching_rule_when_continue_processing_is_false(): void
    {
        Queue::fake();

        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        AutomationRule::factory()->alwaysMatches()->create([
            'automation_id' => $automation->id,
            'sort_order' => 1,
            'continue_processing' => false,
        ]);
        AutomationRule::factory()->alwaysMatches()->create([
            'automation_id' => $automation->id,
            'sort_order' => 2,
        ]);

        app(AutomationEngine::class)->process('ticket.updated', ['ticket' => ['id' => 1], 'changes' => []]);

        Queue::assertPushed(ExecuteAutomationRuleJob::class, 1);
    }

    #[Test]
    public function it_creates_automation_run_record(): void
    {
        Queue::fake();

        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);

        app(AutomationEngine::class)->process('ticket.updated', ['ticket' => ['id' => 1], 'changes' => []]);

        $this->assertDatabaseHas('automation_runs', [
            'automation_id' => $automation->id,
            'trigger_name' => 'ticket.updated',
        ]);
    }
}
