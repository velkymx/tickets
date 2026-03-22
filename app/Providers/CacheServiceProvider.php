<?php

namespace App\Providers;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Release;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->clearLookupCache();
    }

    /**
     * Register model events to clear lookup cache.
     */
    protected function clearLookupCache(): void
    {
        $lookupModels = [
            Type::class,
            Importance::class,
            Status::class,
        ];

        foreach ($lookupModels as $model) {
            $model::created(function () {
                Cache::forget('ticket_lookups');
            });

            $model::updated(function () {
                Cache::forget('ticket_lookups');
            });

            $model::deleted(function () {
                Cache::forget('ticket_lookups');
            });
        }

        Project::created(function () {
            Cache::forget('ticket_lookups');
        });
        Project::updated(function () {
            Cache::forget('ticket_lookups');
        });
        Project::deleted(function () {
            Cache::forget('ticket_lookups');
        });

        User::created(function () {
            Cache::forget('ticket_lookups');
        });
        User::updated(function () {
            Cache::forget('ticket_lookups');
        });
        User::deleted(function () {
            Cache::forget('ticket_lookups');
        });

        Release::created(function () {
            Cache::forget('ticket_lookups');
        });
        Release::updated(function () {
            Cache::forget('ticket_lookups');
        });
        Release::deleted(function () {
            Cache::forget('ticket_lookups');
        });

        Milestone::created(function () {
            Cache::forget('ticket_lookups');
        });
        Milestone::updated(function () {
            Cache::forget('ticket_lookups');
        });
        Milestone::deleted(function () {
            Cache::forget('ticket_lookups');
        });
    }
}
