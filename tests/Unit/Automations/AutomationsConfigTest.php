<?php

namespace Tests\Unit\Automations;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutomationsConfigTest extends TestCase
{
    #[Test]
    public function it_has_trigger_registry(): void
    {
        $triggers = config('automations.triggers');
        $this->assertIsArray($triggers);
        $this->assertArrayHasKey('ticket.created', $triggers);
        $this->assertArrayHasKey('ticket.updated', $triggers);
    }

    #[Test]
    public function each_trigger_has_required_keys(): void
    {
        foreach (config('automations.triggers') as $slug => $trigger) {
            $this->assertArrayHasKey('label', $trigger, "Trigger {$slug} missing label");
            $this->assertArrayHasKey('event', $trigger, "Trigger {$slug} missing event");
            $this->assertArrayHasKey('context_builder', $trigger, "Trigger {$slug} missing context_builder");
        }
    }
}
