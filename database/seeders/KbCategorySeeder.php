<?php

namespace Database\Seeders;

use App\Models\KbCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KbCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Architecture', 'description' => 'System design and architectural decisions', 'sort_order' => 1],
            ['name' => 'Code Style', 'description' => 'Coding standards and style guides', 'sort_order' => 2],
            ['name' => 'How-To', 'description' => 'Step-by-step guides and tutorials', 'sort_order' => 3],
            ['name' => 'Reference', 'description' => 'Quick reference and cheat sheets', 'sort_order' => 4],
            ['name' => 'Ideas', 'description' => 'Feature ideas and proposals', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            KbCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                $category + ['slug' => Str::slug($category['name'])]
            );
        }
    }
}
