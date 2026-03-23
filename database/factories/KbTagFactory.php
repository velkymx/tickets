<?php

namespace Database\Factories;

use App\Models\KbTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KbTagFactory extends Factory
{
    protected $model = KbTag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
