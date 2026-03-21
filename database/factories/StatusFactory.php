<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'slug' => $this->faker->slug(2),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => $this->faker->randomElement([5, 8, 9]),
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => $this->faker->randomElement([1, 2, 3, 4, 6, 7]),
        ]);
    }
}
