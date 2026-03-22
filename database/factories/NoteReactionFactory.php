<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\NoteReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteReactionFactory extends Factory
{
    protected $model = NoteReaction::class;

    public function definition(): array
    {
        return [
            'note_id' => Note::factory(),
            'user_id' => User::factory(),
            'emoji' => $this->faker->randomElement(NoteReaction::ALLOWED_EMOJIS),
        ];
    }
}
