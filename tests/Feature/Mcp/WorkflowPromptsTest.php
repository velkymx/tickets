<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Prompts\CloseOutTicketPrompt;
use App\Mcp\Prompts\StandupDigestPrompt;
use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\Servers\TicketsServer;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowPromptsTest extends TestCase
{
    #[Test]
    public function server_lists_workflow_prompts(): void
    {
        TicketsServer::prompts()->assertRegistered([
            TriageTicketPrompt::class,
            StandupDigestPrompt::class,
            CloseOutTicketPrompt::class,
        ]);
    }

    #[Test]
    public function triage_prompt_orders_pulse_before_action(): void
    {
        $body = $this->promptBody(TriageTicketPrompt::class, ['ticket_id' => 1]);

        $this->assertStringContainsString('get-pulse-tool', $body);
        $this->assertStringContainsString('update-ticket-tool', $body);
        $this->assertLessThan(
            strpos($body, 'update-ticket-tool'),
            strpos($body, 'get-pulse-tool')
        );
    }

    #[Test]
    public function standup_prompt_orders_scope_before_digest(): void
    {
        $body = $this->promptBody(StandupDigestPrompt::class, ['milestone_id' => 1]);

        $this->assertStringContainsString('list-milestones-tool', $body);
        $this->assertStringContainsString('get-pulse-tool', $body);
        $this->assertLessThan(
            strpos($body, 'get-pulse-tool'),
            strpos($body, 'list-milestones-tool')
        );
    }

    #[Test]
    public function close_out_prompt_orders_resolve_before_close(): void
    {
        $body = $this->promptBody(CloseOutTicketPrompt::class, ['ticket_id' => 1]);

        $this->assertStringContainsString('resolve-note-tool', $body);
        $this->assertStringContainsString('update-ticket-tool', $body);
        $this->assertLessThan(
            strpos($body, 'update-ticket-tool'),
            strpos($body, 'resolve-note-tool')
        );
    }

    #[Test]
    public function prompts_reference_only_registered_tools(): void
    {
        $registered = collect((new \ReflectionClass(TicketsServer::class))->getDefaultProperties()['tools'] ?? [])
            ->map(fn ($class) => Str::kebab(class_basename($class)))
            ->all();

        $this->assertNotEmpty($registered);

        foreach ([TriageTicketPrompt::class, StandupDigestPrompt::class, CloseOutTicketPrompt::class] as $prompt) {
            $body = $this->promptBody($prompt, $this->argsFor($prompt));

            preg_match_all('/\b[a-z0-9-]+-tool\b/', $body, $matches);

            $this->assertNotEmpty($matches[0]);

            foreach (array_unique($matches[0]) as $referenced) {
                $this->assertContains($referenced, $registered, "prompt references unregistered tool {$referenced}");
            }
        }
    }

    private function promptBody(string $prompt, array $args): string
    {
        return (string) (new $prompt)->handle(new Request($args))->content();
    }

    private function argsFor(string $prompt): array
    {
        return $prompt === StandupDigestPrompt::class ? ['milestone_id' => 1] : ['ticket_id' => 1];
    }
}
