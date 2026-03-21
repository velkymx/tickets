<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'body' => $this->faker->paragraph(),
            'hours' => 0,
            'notetype' => 'message',
            'hide' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'hide' => true,
        ]);
    }

    public function changelog(): static
    {
        return $this->state(fn (array $attributes) => [
            'body' => '<ul><li>Status changed to Closed</li></ul>',
            'notetype' => 'changelog',
            'hours' => 0,
        ]);
    }

    public function withHours(float $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'hours' => $hours,
        ]);
    }
}
