<?php

namespace Tests\Feature\Automations;

use App\Models\Automation;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    public function store_rejects_an_unknown_action_type(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Bad Action',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([['type' => 'launch_missiles', 'config' => []]]),
        ]);

        $response->assertSessionHasErrors('actions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Bad Action']);
    }

    #[Test]
    public function update_rejects_an_unknown_action_type(): void
    {
        $user = User::factory()->create(['admin' => true]);
        $automation = Automation::factory()->create(['name' => 'Original']);

        $response = $this->actingAs($user)->put(route('automations.update', $automation), [
            'name' => 'Original',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([['type' => 'launch_missiles', 'config' => []]]),
        ]);

        $response->assertSessionHasErrors('actions_json');
    }

    #[Test]
    public function store_rejects_a_non_array_conditions_document(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Bad Shape',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode('justastring'),
            'actions_json' => json_encode([]),
        ]);

        $response->assertSessionHasErrors('conditions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Bad Shape']);
    }

    public static function invalidConditionsDocuments(): array
    {
        return [
            'list' => [json_encode([['operator' => 'equals', 'field' => 'ticket.id', 'value' => 1]])],
            'unknown object' => [json_encode(['foo' => 'bar'])],
            'unknown operator' => [json_encode(['operator' => 'explode', 'field' => 'ticket.id'])],
            'group with a bad child' => [json_encode(['all' => [['operator' => 'equals', 'field' => 'ticket.id', 'value' => 1], ['nope' => true]]])],
        ];
    }

    #[Test]
    #[DataProvider('invalidConditionsDocuments')]
    public function store_rejects_conditions_without_a_valid_shape(string $document): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Bad Shape',
            'trigger' => 'ticket.created',
            'conditions_json' => $document,
            'actions_json' => json_encode([]),
        ]);

        $response->assertSessionHasErrors('conditions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Bad Shape']);
    }

    public static function validConditionsDocuments(): array
    {
        return [
            'empty group' => [json_encode(['all' => []])],
            'leaf' => [json_encode(['operator' => 'equals', 'field' => 'ticket.id', 'value' => 1])],
            'nested groups' => [json_encode(['any' => [['all' => [['operator' => 'is_not_empty', 'field' => 'ticket.subject']]]]])],
        ];
    }

    #[Test]
    #[DataProvider('validConditionsDocuments')]
    public function store_accepts_conditions_with_a_valid_shape(string $document): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Good Shape',
            'trigger' => 'ticket.created',
            'conditions_json' => $document,
            'actions_json' => json_encode([]),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('automations', ['name' => 'Good Shape']);
    }

    #[Test]
    public function store_rejects_an_action_without_a_config_url(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->post(route('automations.store'), [
            'name' => 'Missing Url',
            'trigger' => 'ticket.created',
            'conditions_json' => json_encode(['all' => []]),
            'actions_json' => json_encode([['type' => 'call_webhook', 'config' => []]]),
        ]);

        $response->assertSessionHasErrors('actions_json');
        $this->assertDatabaseMissing('automations', ['name' => 'Missing Url']);
    }

    #[Test]
    public function fields_rejects_an_unknown_trigger(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $this->actingAs($user)->get(route('automations.fields', ['trigger' => 'nope.not real']))->assertNotFound();
    }

    #[Test]
    public function fields_lists_a_known_trigger(): void
    {
        $user = User::factory()->create(['admin' => true]);

        $response = $this->actingAs($user)->get(route('automations.fields', ['trigger' => 'ticket.created']));

        $response->assertOk();
        $this->assertNotEmpty($response->json());
    }
}
