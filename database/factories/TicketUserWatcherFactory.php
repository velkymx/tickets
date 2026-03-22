<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketUserWatcher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketUserWatcherFactory extends Factory
{
    protected $model = TicketUserWatcher::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'muted' => false,
        ];
    }
}
