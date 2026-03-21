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
            'name' => $this->faker->randomElement(['Critical', 'High', 'Medium', 'Low']),
            'icon' => $this->faker->randomElement([
                'fas fa-fire text-danger',
                'fas fa-exclamation text-warning',
                'fas fa-minus text-info',
                'fas fa-arrow-down text-secondary',
            ]),
            'class' => $this->faker->randomElement(['danger', 'warning', 'info', 'secondary']),
        ];
    }
}
