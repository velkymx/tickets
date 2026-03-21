<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketEstimate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketEstimateFactory extends Factory
{
    protected $model = TicketEstimate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'storypoints' => $this->faker->numberBetween(1, 13),
        ];
    }
}
