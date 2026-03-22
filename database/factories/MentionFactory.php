<?php

namespace Database\Factories;

use App\Models\Mention;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentionFactory extends Factory
{
    protected $model = Mention::class;

    public function definition(): array
    {
        return [
            'note_id' => Note::factory(),
            'user_id' => User::factory(),
        ];
    }
}
