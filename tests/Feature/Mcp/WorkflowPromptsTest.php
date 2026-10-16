<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Prompts\CloseOutTicketPrompt;
use App\Mcp\Prompts\StandupDigestPrompt;
use App\Mcp\Prompts\TriageTicketPrompt;
use App\Mcp\Servers\TicketsServer;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class WorkflowPromptsTest extends TestCase
{
    use SeedsDatabase;

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
        $user = User::factory()->create();

        $body = $this->promptBody(TriageTicketPrompt::class, ['ticket_id' => 1], $user);

        $this->assertLessThan(
            strpos($body, 'update-ticket-tool'),
            strpos($body, 'get-pulse-tool')
        );
    }

    #[Test]
    public function standup_prompt_orders_scope_before_digest(): void
    {
        $user = User::factory()->create();

        $body = $this->promptBody(StandupDigestPrompt::class, ['milestone_id' => 1], $user);

        $this->assertLessThan(
            strpos($body, 'get-pulse-tool'),
            strpos($body, 'list-milestones-tool')
        );
    }

    #[Test]
    public function close_out_prompt_orders_resolve_before_close(): void
    {
        $user = User::factory()->create();

        $body = $this->promptBody(CloseOutTicketPrompt::class, ['ticket_id' => 1], $user);

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
            $body = $this->promptBody($prompt, $this->argsFor($prompt), User::factory()->create());

            preg_match_all('/\b[a-z0-9-]+-tool\b/', $body, $matches);

            $this->assertNotEmpty($matches[0]);

            foreach (array_unique($matches[0]) as $referenced) {
                $this->assertContains($referenced, $registered, "prompt references unregistered tool {$referenced}");
            }
        }
    }

    private function promptBody(string $prompt, array $args, User $user): string
    {
        $response = TicketsServer::actingAs($user)->prompt($prompt, $args);

        $response->assertOk();

        // TestResponse exposes no public body accessor, so read its
        // content seam directly. This asserts on the served text,
        // not on the prompt implementation.
        $content = new \ReflectionMethod($response, 'content');
        $content->setAccessible(true);

        return implode("\n", $content->invoke($response));
    }

    private function argsFor(string $prompt): array
    {
        return $prompt === StandupDigestPrompt::class ? ['milestone_id' => 1] : ['ticket_id' => 1];
    }
}
