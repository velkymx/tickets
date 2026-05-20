<?php

namespace Tests\Feature\Controllers;

use App\Models\Automation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationControllerTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function index_requires_auth(): void
    {
        $this->get(route('automations.index'))->assertRedirect('/login');
    }

    #[Test]
    public function index_lists_automations(): void
    {
        $user = User::factory()->create();
        Automation::factory()->count(3)->create();

        $this->actingAs($user)->get(route('automations.index'))->assertStatus(200);
    }

    #[Test]
    public function create_shows_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('automations.create'))->assertStatus(200);
    }

    #[Test]
    public function store_creates_automation(): void
    {
        $user = User::factory()->create();

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
        $user = User::factory()->create();
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
    public function destroy_deletes_automation(): void
    {
        $user = User::factory()->create();
        $automation = Automation::factory()->create();

        $this->actingAs($user)->delete(route('automations.destroy', $automation))->assertRedirect();

        $this->assertDatabaseMissing('automations', ['id' => $automation->id]);
    }

    #[Test]
    public function show_displays_automation_runs(): void
    {
        $user = User::factory()->create();
        $automation = Automation::factory()->create();

        $this->actingAs($user)->get(route('automations.show', $automation))->assertStatus(200);
    }
}
