<?php

namespace Database\Factories;

use App\Models\Automation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutomationFactory extends Factory
{
    protected $model = Automation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'trigger' => 'ticket.updated',
            'enabled' => true,
            'stop_after_match' => true,
            'created_by' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => ['enabled' => false]);
    }

    public function forTrigger(string $trigger): static
    {
        return $this->state(fn (array $attributes) => ['trigger' => $trigger]);
    }
}
