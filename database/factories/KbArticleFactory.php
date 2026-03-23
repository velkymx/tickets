<?php

namespace Database\Factories;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KbArticleFactory extends Factory
{
    protected $model = KbArticle::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.$this->faker->unique()->randomNumber(5),
            'body_markdown' => $this->faker->paragraphs(3, true),
            'body_html' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'category_id' => KbCategory::factory(),
            'user_id' => User::factory(),
            'owner_id' => fn (array $attrs) => $attrs['user_id'],
            'status' => 'draft',
            'visibility' => 'internal',
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'published_at' => now(),
            'reviewed_at' => now(),
        ]);
    }

    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deprecated',
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'internal',
        ]);
    }

    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'restricted',
        ]);
    }
}
