<?php

namespace App\Providers;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Release;
use App\Models\Ticket;
use App\Policies\MilestonePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReleasePolicy;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Milestone::class, MilestonePolicy::class);
        Gate::policy(Release::class, ReleasePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
