<?php

namespace Database\Factories;

use App\Models\Release;
use App\Models\ReleaseTicket;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReleaseTicketFactory extends Factory
{
    protected $model = ReleaseTicket::class;

    public function definition(): array
    {
        return [
            'release_id' => Release::factory(),
            'ticket_id' => Ticket::factory(),
        ];
    }
}
