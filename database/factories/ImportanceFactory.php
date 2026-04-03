<?php

namespace Database\Factories;

use App\Models\Importance;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportanceFactory extends Factory
{
    protected $model = Importance::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['trivial', 'minor', 'major', 'critical', 'blocker']),
            'icon' => null,
            'class' => null,
        ];
    }
}
