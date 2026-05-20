<?php

namespace Database\Factories;

use App\Models\AutomationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationRuleFactory extends Factory
{
    protected $model = AutomationRule::class;

    public function definition(): array
    {
        return [
            'automation_id' => \App\Models\Automation::factory(),
            'name' => $this->faker->words(2, true),
            'sort_order' => 0,
            'conditions_json' => ['all' => []],
            'continue_processing' => false,
        ];
    }

    public function alwaysMatches(): static
    {
        return $this->state(fn (array $attributes) => [
            'conditions_json' => ['all' => []],
        ]);
    }
}
