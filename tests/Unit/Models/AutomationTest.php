<?php

namespace Tests\Unit\Models;

use App\Models\Automation;
use App\Models\AutomationRule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $a = new Automation();
        $this->assertContains('name', $a->getFillable());
        $this->assertContains('trigger', $a->getFillable());
        $this->assertContains('enabled', $a->getFillable());
    }

    #[Test]
    public function it_casts_enabled_to_boolean(): void
    {
        $a = Automation::factory()->create(['enabled' => 1]);
        $this->assertIsBool($a->enabled);
    }

    #[Test]
    public function it_has_rules_relationship(): void
    {
        $a = Automation::factory()->create();
        AutomationRule::factory()->create(['automation_id' => $a->id]);
        $this->assertCount(1, $a->rules);
    }
}
