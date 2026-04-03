<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketViewFactory extends Factory
{
    protected $model = TicketView::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
        ];
    }
}
