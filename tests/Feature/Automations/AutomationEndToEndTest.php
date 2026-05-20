<?php

namespace Tests\Feature\Automations;

use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\AutomationRule;
use App\Models\Ticket;
use App\Automations\Support\SsrfGuard;
use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Type;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationEndToEndTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function creating_ticket_queues_matching_automation_job(): void
    {
        Queue::fake();

        $automation = Automation::factory()->forTrigger('ticket.created')->create();
        AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);

        $user = User::factory()->create();
        $type = Type::factory()->create();
        $status = Status::factory()->create();
        $importance = Importance::factory()->create();
        $milestone = Milestone::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)->post('/tickets', [
            'subject' => 'Test automation trigger',
            'description' => 'Test',
            'type_id' => $type->id,
            'status_id' => $status->id,
            'importance_id' => $importance->id,
            'milestone_id' => $milestone->id,
            'project_id' => $project->id,
        ]);

        Queue::assertPushed(\App\Jobs\ExecuteAutomationRuleJob::class);
    }

    #[Test]
    public function job_sends_webhook_and_logs_run(): void
    {
        Http::fake(['https://hooks.example.com/*' => Http::response('ok', 200)]);

        $this->mock(SsrfGuard::class)->shouldReceive('validate')->andReturn(null);

        config(['queue.default' => 'sync']);

        $automation = Automation::factory()->forTrigger('ticket.updated')->create();
        $rule = AutomationRule::factory()->alwaysMatches()->create(['automation_id' => $automation->id]);
        AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'type' => 'call_webhook',
            'config_json' => [
                'url' => 'https://hooks.example.com/test',
                'method' => 'POST',
                'headers' => [],
                'payload' => ['ticket_id' => '{{ ticket.id }}'],
            ],
        ]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);

        $this->actingAs($user)->put("/tickets/update/{$ticket->id}", [
            'subject' => 'Updated subject',
        ]);

        $this->assertDatabaseHas('automation_runs', [
            'automation_id' => $automation->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('automation_action_logs', [
            'status' => 'success',
            'response_code' => 200,
        ]);
    }
}
