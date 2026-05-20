<?php

namespace App\Automations\Drivers;

use App\Automations\Contracts\AutomationActionDriverInterface;
use App\Automations\Support\PayloadRenderer;
use App\Automations\Support\SsrfGuard;
use App\Models\AutomationAction;
use App\Models\AutomationActionLog;
use App\Models\AutomationRun;
use Illuminate\Support\Facades\Http;

class WebhookActionDriver implements AutomationActionDriverInterface
{
    public function __construct(
        private readonly SsrfGuard $ssrfGuard,
        private readonly PayloadRenderer $renderer,
    ) {}

    public function execute(AutomationAction $action, array $context, AutomationRun $run): void
    {
        $config = $action->config_json;
        $url = $config['url'];

        $this->ssrfGuard->validate($url);

        $payload = $this->renderer->render($config['payload'] ?? [], $context);
        $payloadJson = json_encode($payload);

        if (strlen($payloadJson) > 262144) {
            throw new \RuntimeException('Payload exceeds 256KB limit');
        }

        $method = strtolower($config['method'] ?? 'post');
        $headers = $config['headers'] ?? [];

        $response = Http::timeout(10)
            ->withHeaders($headers)
            ->$method($url, $payload);

        AutomationActionLog::create([
            'automation_run_id' => $run->id,
            'automation_action_id' => $action->id,
            'status' => $response->successful() ? 'success' : 'failed',
            'request_url' => $url,
            'request_payload' => $payloadJson,
            'response_code' => $response->status(),
            'response_body' => substr((string) $response->body(), 0, 65535),
            'attempts' => 1,
            'executed_at' => now(),
        ]);
    }
}
