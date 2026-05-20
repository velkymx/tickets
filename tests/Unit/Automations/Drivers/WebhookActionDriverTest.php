<?php

namespace Tests\Unit\Automations\Drivers;

use App\Automations\Drivers\WebhookActionDriver;
use App\Automations\Exceptions\SsrfException;
use App\Models\Automation;
use App\Models\AutomationAction;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class WebhookActionDriverTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_sends_post_request_and_logs_response(): void
    {
        Http::fake(['https://example.com/webhook' => Http::response('ok', 200)]);

        $automation = Automation::factory()->create();
        $rule = AutomationRule::factory()->create(['automation_id' => $automation->id]);
        $action = AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'type' => 'call_webhook',
            'config_json' => [
                'url' => 'https://example.com/webhook',
                'method' => 'POST',
                'headers' => [],
                'payload' => ['ticket_id' => '{{ ticket.id }}'],
            ],
        ]);
        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'automation_rule_id' => $rule->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'running',
            'context_json' => ['ticket' => ['id' => 5], 'changes' => []],
        ]);

        $driver = app(WebhookActionDriver::class);
        $driver->execute($action, ['ticket' => ['id' => 5], 'changes' => []], $run);

        $this->assertDatabaseHas('automation_action_logs', [
            'automation_run_id' => $run->id,
            'automation_action_id' => $action->id,
            'status' => 'success',
            'response_code' => 200,
        ]);
    }

    #[Test]
    public function it_blocks_ssrf_urls_and_throws(): void
    {
        $automation = Automation::factory()->create();
        $rule = AutomationRule::factory()->create(['automation_id' => $automation->id]);
        $action = AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'type' => 'call_webhook',
            'config_json' => [
                'url' => 'http://127.0.0.1/evil',
                'method' => 'POST',
                'headers' => [],
                'payload' => [],
            ],
        ]);
        $run = AutomationRun::create([
            'automation_id' => $automation->id,
            'automation_rule_id' => $rule->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'running',
            'context_json' => [],
        ]);

        $this->expectException(SsrfException::class);
        app(WebhookActionDriver::class)->execute($action, [], $run);
    }
}
