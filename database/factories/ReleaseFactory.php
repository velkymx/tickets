<?php

namespace Database\Factories;

use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReleaseFactory extends Factory
{
    protected $model = Release::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->paragraph(),
            'started_at' => null,
            'completed_at' => null,
            'user_id' => User::factory(),
        ];
    }

    public function withDates(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
            'completed_at' => now()->addDays(14),
        ]);
    }
}
