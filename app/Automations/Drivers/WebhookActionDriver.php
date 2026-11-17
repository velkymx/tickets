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
    /** HTTP verbs a webhook may use - prevents dynamic dispatch to arbitrary PendingRequest methods. */
    private const ALLOWED_METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    private const MAX_REDIRECTS = 5;

    private const REDIRECT_STATUSES = [301, 302, 307, 308];

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

        if (! in_array($method, self::ALLOWED_METHODS, true)) {
            throw new \InvalidArgumentException("Unsupported webhook method [{$method}].");
        }

        $headers = $config['headers'] ?? [];
        $currentUrl = $url;

        $this->ssrfGuard->validate($currentUrl);

        $response = null;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $response = Http::timeout(10)
                ->withoutRedirecting()
                ->withHeaders($headers)
                ->$method($currentUrl, $payload);

            $location = $response->header('Location');

            if (! in_array($response->status(), self::REDIRECT_STATUSES, true) || ! $location) {
                break;
            }

            $nextUrl = $this->resolveRedirectUrl($currentUrl, $location);
            $this->ssrfGuard->validate($nextUrl);

            if (! $this->ssrfGuard->sameOrigin($currentUrl, $nextUrl)) {
                $headers = $this->ssrfGuard->stripSensitiveHeaders($headers);
            }

            $currentUrl = $nextUrl;
        }

        AutomationActionLog::create([
            'automation_run_id' => $run->id,
            'automation_action_id' => $action->id,
            'status' => $response->successful() ? 'success' : 'failed',
            'request_url' => $currentUrl,
            'request_payload' => $payloadJson,
            'response_code' => $response->status(),
            'response_body' => substr((string) $response->body(), 0, 65535),
            'attempts' => 1,
            'executed_at' => now(),
        ]);
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        if (parse_url($location, PHP_URL_HOST) !== null) {
            return $location;
        }

        $current = parse_url($currentUrl);
        $base = ($current['scheme'] ?? 'https').'://'.($current['host'] ?? '');

        if (isset($current['port'])) {
            $base .= ':'.$current['port'];
        }

        if (! str_starts_with($location, '/')) {
            $path = $current['path'] ?? '/';
            $location = substr($path, 0, strrpos($path, '/') + 1).$location;
        }

        return $base.$location;
    }
}
