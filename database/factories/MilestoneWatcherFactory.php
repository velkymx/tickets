<?php

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\MilestoneWatcher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MilestoneWatcherFactory extends Factory
{
    protected $model = MilestoneWatcher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'milestone_id' => Milestone::factory(),
        ];
    }
}
