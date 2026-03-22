<?php

namespace App\Providers;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Release;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use App\Services\TicketPulseService;
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
        $this->registerTicketPulseInvalidation();
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

    protected function registerTicketPulseInvalidation(): void
    {
        $invalidateTicket = function (Ticket $ticket): void {
            app(TicketPulseService::class)->invalidatePulse($ticket->id);
        };

        Ticket::created($invalidateTicket);
        Ticket::updated($invalidateTicket);
        Ticket::deleted($invalidateTicket);

        $invalidateNote = function (Note $note): void {
            app(TicketPulseService::class)->invalidatePulse($note->ticket_id);
        };

        Note::created($invalidateNote);
        Note::updated($invalidateNote);
        Note::deleted($invalidateNote);
    }
}
