<?php

namespace Tests\Feature\Automations;

use App\Models\Automation;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class AutomationJsonValidationTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function store_rejects_malformed_conditions_json(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Bad JSON',
            'trigger' => 'ticket.created',
            'conditions_json' => '{not valid json',
            'actions_json' => json_encode([]),
        ]);

        $response->assertSessionHasErrors('conditions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Bad JSON']);
    }

    #[Test]
    public function store_rejects_malformed_actions_json(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Bad Actions',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => '[broken',
        ]);

        $response->assertSessionHasErrors('actions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Bad Actions']);
    }

    #[Test]
    public function update_rejects_malformed_conditions_json(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($user)->put(route('automations.update', $automation), [
            'name' => 'Original',
            'trigger' => 'ticket.created',
            'conditions_json' => '{not valid json',
            'actions_json' => json_encode([]),
        ]);

        $response->assertSessionHasErrors('conditions_json');
    }
}
