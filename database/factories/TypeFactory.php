<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class TypeFactory extends Factory
{
    protected $model = Type::class;

    public function definition(): array
    {
        $icons = ['fas fa-bug', 'fas fa-star', 'fas fa-check', 'fas fa-arrow-up', 'fas fa-book'];

        return [
            'name' => $this->faker->randomElement(['Bug', 'Feature', 'Task', 'Improvement', 'Story']),
            'icon' => $this->faker->randomElement($icons),
        ];
    }
}
