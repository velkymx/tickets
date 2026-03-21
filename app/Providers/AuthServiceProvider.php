<?php

namespace App\Providers;

use App\Models\Milestone;
use App\Models\Ticket;
use App\Policies\MilestonePolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Ticket::class => TicketPolicy::class,
        Milestone::class => MilestonePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
