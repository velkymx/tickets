<?php

namespace Tests\Feature\Controllers;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_lists_automations(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create(['name' => 'Nightly triage']);

        $response = $this->actingAs($user)->get(route('automations.index'));

        $response->assertOk();
        $response->assertSee('Nightly triage');
        $this->assertTrue($response->viewData('automations')->contains($automation));
    }

    #[Test]
    public function create_shows_form(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->get(route('automations.create'));

        $response->assertOk();
        $response->assertViewIs('automations.create');
        $response->assertSee('name="name"', false);
    }

    #[Test]
    public function store_creates_automation(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'My Automation',
            'trigger' => 'ticket.updated',
            'description' => 'Test',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([]),
        ])->assertRedirect();

        $this->assertDatabaseHas('automations', ['name' => 'My Automation', 'trigger' => 'ticket.updated']);
    }

    #[Test]
    public function update_modifies_automation(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->put(route('automations.update', $automation), [
            'name' => 'New Name',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([]),
        ])->assertRedirect();

        $this->assertDatabaseHas('automations', ['id' => $automation->id, 'name' => 'New Name']);
    }

    #[Test]
    public function store_defaults_checkboxes_to_disabled_when_absent(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Disabled Automation',
            'trigger' => 'ticket.updated',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([]),
        ])->assertRedirect();

        $automation = Automation::where('name', 'Disabled Automation')->firstOrFail();

        $this->assertFalse($automation->enabled);
        $this->assertFalse($automation->stop_after_match);
    }

    #[Test]
    public function update_can_disable_an_automation(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create([
            'enabled' => true,
            'stop_after_match' => true,
        ]);

        $this->actingAs($user)->put(route('automations.update', $automation), [
            'name' => $automation->name,
            'trigger' => $automation->trigger,
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([]),
        ])->assertRedirect();

        $automation->refresh();

        $this->assertFalse($automation->enabled);
        $this->assertFalse($automation->stop_after_match);
    }

    #[Test]
    public function update_can_enable_an_automation(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create([
            'enabled' => false,
            'stop_after_match' => false,
        ]);

        $this->actingAs($user)->put(route('automations.update', $automation), [
            'name' => $automation->name,
            'trigger' => $automation->trigger,
            'enabled' => '1',
            'stop_after_match' => '1',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([]),
        ])->assertRedirect();

        $automation->refresh();

        $this->assertTrue($automation->enabled);
        $this->assertTrue($automation->stop_after_match);
    }

    #[Test]
    public function destroy_deletes_automation(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create();

        $this->actingAs($user)->delete(route('automations.destroy', $automation))->assertRedirect();

        $this->assertDatabaseMissing('automations', ['id' => $automation->id]);
    }

    #[Test]
    public function show_displays_automation_runs(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create();
        AutomationRun::create([
            'automation_id' => $automation->id,
            'trigger_name' => 'ticket.updated',
            'status' => 'success',
            'context_json' => [],
        ]);

        $response = $this->actingAs($user)->get(route('automations.show', $automation));

        $response->assertOk();
        $response->assertSee('ticket.updated');
        $response->assertSee('success');
        $this->assertSame(1, $response->viewData('runs')->total());
    }
}
