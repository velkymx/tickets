<?php

namespace App\Providers;

use App\Contracts\SearchableRepository;
use App\Models\KbArticle;
use App\Models\Milestone;
use App\Models\Note;
use App\Models\Project;
use App\Models\Release;
use App\Models\Ticket;
use App\Observers\NoteObserver;
use App\Policies\KbArticlePolicy;
use App\Policies\MilestonePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\ReleasePolicy;
use App\Policies\TicketPolicy;
use App\Automations\ContextBuilders\TicketCreatedContextBuilder;
use App\Automations\ContextBuilders\TicketUpdatedContextBuilder;
use App\Automations\Services\AutomationEngine;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Services\KbSearchService;
use App\ViewComposers\NotificationComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            SearchableRepository::class,
            KbSearchService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerSortableMacro();

        Note::observe(NoteObserver::class);

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Milestone::class, MilestonePolicy::class);
        Gate::policy(Release::class, ReleasePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(KbArticle::class, KbArticlePolicy::class);

        View::composer('layouts.app', NotificationComposer::class);

        RateLimiter::for('api', function ($request) {
            $userId = $request->user()?->id ?? $request->attributes->get('api_user')?->id;

            return Limit::perMinute(60)->by($userId ?: $request->ip());
        });

        RateLimiter::for('uploads', function ($request) {
            if (app()->environment('testing')) {
                return Limit::none();
            }

            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        Event::listen(TicketCreated::class, function (TicketCreated $event) {
            $context = (new TicketCreatedContextBuilder())->build($event);
            app(AutomationEngine::class)->process('ticket.created', $context);
        });

        Event::listen(TicketUpdated::class, function (TicketUpdated $event) {
            $context = (new TicketUpdatedContextBuilder())->build($event);
            app(AutomationEngine::class)->process('ticket.updated', $context);
        });
    }

    /**
     * Register a reusable ->sortable() query macro for paginated list views.
     *
     * Usage: $query->sortable(['subject', 'status_id' => 'status_id'], ['importance_id', 'desc'])
     *   - $allowed: whitelist of sortable columns. A string value is a column
     *     whose request key equals itself; a key => column pair maps a public
     *     sort key to a real column (for aliasing). Anything not whitelisted is
     *     ignored, so the raw ?sort= value never reaches orderBy (injection-safe).
     *   - $default: [column, direction] applied when no valid sort is requested.
     */
    protected function registerSortableMacro(): void
    {
        Builder::macro('sortable', function (array $allowed, array $default) {
            /** @var Builder $this */
            $columns = [];
            foreach ($allowed as $key => $column) {
                $columns[is_int($key) ? $column : $key] = $column;
            }

            $request = request();
            $sort = $request->query('sort');
            $dir = strtolower((string) $request->query('dir')) === 'desc' ? 'desc' : 'asc';

            if ($sort !== null && isset($columns[$sort])) {
                return $this->orderBy($columns[$sort], $dir);
            }

            [$defaultColumn, $defaultDir] = $default;

            return $this->orderBy($defaultColumn, $defaultDir);
        });
    }
}
