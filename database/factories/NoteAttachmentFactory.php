<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteAttachmentFactory extends Factory
{
    protected $model = NoteAttachment::class;

    public function definition(): array
    {
        return [
            'note_id' => Note::factory(),
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'filename' => $this->faker->fileName(),
            'path' => 'attachments/1/test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 100000),
        ];
    }
}
