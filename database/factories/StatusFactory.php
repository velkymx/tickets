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
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => 5,
            'name' => 'Closed',
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Open',
        ]);
    }
}
