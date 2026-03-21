<?php

namespace Database\Factories;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'type_id' => Type::factory(),
            'user_id' => User::factory(),
            'status_id' => Status::factory()->open(),
            'importance_id' => Importance::factory(),
            'milestone_id' => Milestone::factory(),
            'project_id' => Project::factory(),
            'user_id2' => User::factory(),
            'due_at' => null,
            'closed_at' => null,
            'estimate' => 0,
            'actual' => 0,
            'storypoints' => 0,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => Status::factory()->closed(),
            'closed_at' => now(),
        ]);
    }

    public function assigned(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id2' => $user->id,
        ]);
    }

    public function withProject(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => Project::factory(),
        ]);
    }
}
