<?php

namespace Database\Seeders;

use App\Models\KbTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KbTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'API',
            'Backend',
            'Bug Fix',
            'Database',
            'Deployment',
            'Documentation',
            'Frontend',
            'Performance',
            'Security',
            'Testing',
        ];

        foreach ($tags as $name) {
            KbTag::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'slug' => Str::slug($name)]
            );
        }
    }
}
