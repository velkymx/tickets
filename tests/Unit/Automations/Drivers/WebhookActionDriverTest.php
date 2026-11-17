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
    public function it_rejects_a_non_http_verb_method(): void
    {
        Http::fake(['https://example.com/webhook' => Http::response('ok', 200)]);

        $automation = Automation::factory()->create();
        $rule = AutomationRule::factory()->create(['automation_id' => $automation->id]);
        $action = AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'type' => 'call_webhook',
            'config_json' => [
                'url' => 'https://example.com/webhook',
                'method' => 'sink', // not an HTTP verb - must be rejected
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

        $this->expectException(\InvalidArgumentException::class);
        app(WebhookActionDriver::class)->execute($action, [], $run);
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

    #[Test]
    public function it_follows_redirects_and_revalidates_each_hop(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('', 302, ['Location' => 'https://example.com/final']),
            'https://example.com/final' => Http::response('ok', 200),
        ]);

        [$action, $run] = $this->webhookAction('https://example.com/webhook');

        app(WebhookActionDriver::class)->execute($action, [], $run);

        Http::assertSentCount(2);
        $this->assertDatabaseHas('automation_action_logs', [
            'automation_run_id' => $run->id,
            'request_url' => 'https://example.com/final',
            'response_code' => 200,
        ]);
    }

    #[Test]
    public function it_blocks_redirects_to_internal_targets(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('', 302, ['Location' => 'http://127.0.0.1/evil']),
        ]);

        [$action, $run] = $this->webhookAction('https://example.com/webhook');

        $this->expectException(SsrfException::class);
        app(WebhookActionDriver::class)->execute($action, [], $run);
    }

    #[Test]
    public function it_strips_authorization_when_leaving_the_origin(): void
    {
        Http::fake([
            'https://93.184.215.14/webhook' => Http::response('', 302, ['Location' => 'https://93.184.215.15/final']),
            'https://93.184.215.15/final' => Http::response('ok', 200),
        ]);

        [$action, $run] = $this->webhookAction('https://93.184.215.14/webhook', ['Authorization' => 'Bearer secret']);

        app(WebhookActionDriver::class)->execute($action, [], $run);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://93.184.215.15/final'
                && ! $request->hasHeader('Authorization');
        });
    }

    #[Test]
    public function it_keeps_authorization_on_same_origin_redirects(): void
    {
        Http::fake([
            'https://example.com/webhook' => Http::response('', 302, ['Location' => 'https://example.com/final']),
            'https://example.com/final' => Http::response('ok', 200),
        ]);

        [$action, $run] = $this->webhookAction('https://example.com/webhook', ['Authorization' => 'Bearer secret']);

        app(WebhookActionDriver::class)->execute($action, [], $run);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/final'
                && $request->hasHeader('Authorization', 'Bearer secret');
        });
    }

    private function webhookAction(string $url, array $headers = []): array
    {
        $automation = Automation::factory()->create();
        $rule = AutomationRule::factory()->create(['automation_id' => $automation->id]);
        $action = AutomationAction::factory()->create([
            'automation_rule_id' => $rule->id,
            'type' => 'call_webhook',
            'config_json' => [
                'url' => $url,
                'method' => 'POST',
                'headers' => $headers,
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

        return [$action, $run];
    }
}
