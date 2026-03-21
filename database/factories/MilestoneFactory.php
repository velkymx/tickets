<?php

namespace Database\Factories;

use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

class MilestoneFactory extends Factory
{
    protected $model = Milestone::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'scrummaster_user_id' => null,
            'owner_user_id' => null,
            'start_at' => null,
            'due_at' => null,
            'end_at' => null,
            'active' => true,
        ];
    }

    public function withDates(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'due_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
