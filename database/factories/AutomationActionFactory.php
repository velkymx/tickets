<?php

namespace Database\Factories;

use App\Models\AutomationAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationActionFactory extends Factory
{
    protected $model = AutomationAction::class;

    public function definition(): array
    {
        return [
            'automation_rule_id' => \App\Models\AutomationRule::factory(),
            'type' => 'call_webhook',
            'sort_order' => 0,
            'config_json' => [
                'url' => 'https://example.com/webhook',
                'method' => 'POST',
                'headers' => [],
                'payload' => ['ticket_id' => '{{ ticket.id }}'],
            ],
        ];
    }
}
