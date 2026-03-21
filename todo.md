# Project Issues Tracker

USE LARAVEL 12 PATTERNS
TDD DEVELOPMENT PATTERNS
ALWAYS BUILD FOR PRODUCTION
NO FALLBACKS

---

## Code Review Summary (2026-03-21)

| Severity | Count (Open) |
|----------|-------------|
| CRITICAL | 5 |
| HIGH | 12 |
| MEDIUM | 24 |
| LOW | 12 |

---

## CRITICAL Issues

### C1. Missing `use` imports — Fatal errors on 3 controllers

- [ ] **MilestoneController::store()** (Line 135) — `StoreMilestoneRequest` used but not imported
  - Route `POST /milestone/store/{id}` will crash with `Class not found`
  - Fix: Add `use App\Http\Requests\StoreMilestoneRequest;`

- [ ] **ProjectsController::store()** (Line 77) — `StoreProjectRequest` used but not imported
  - Route `POST /projects/store/{id}` will crash with `Class not found`
  - Fix: Add `use App\Http\Requests\StoreProjectRequest;`

- [ ] **ReleaseController::store()** (Line 89) — `StoreReleaseRequest` used but not imported
  - Route `POST /release/store` will crash with `Class not found`
  - Fix: Add `use App\Http\Requests\StoreReleaseRequest;`

### C2. Undefined method — Fatal error in TicketsController

- [ ] **TicketsController::index()** (Line 102) — `$this->lookups()` does not exist
  - `lookups()` was moved to `TicketService` but this call was never updated
  - Route `GET /tickets` will crash with `Call to undefined method`
  - Fix: Change to `$this->ticketService->getLookups()`

### C3. State-changing GET route — CSRF bypass

- [ ] **Route `GET /tickets/api/{id}`** (web.php:48) — Modifies ticket status via GET request
  - `TicketsController::api()` updates `status_id` but is accessible via GET with no CSRF
  - Any link/image tag can trigger status changes
  - Fix: Change to `Route::post(...)` or `Route::put(...)`

### C4. Missing authorization on TicketsController::api()

- [ ] **TicketsController::api()** (Line 327-344) — No `$this->authorize('update', $ticket)` call
  - Any authenticated user can change the status of ANY ticket via AJAX
  - Fix: Add `$this->authorize('update', $ticket);` after findOrFail

### C5. board.blade.php AJAX uses GET for status update

- [ ] **board.blade.php** (Line 74-76) — `fetch(url)` with no method = GET request
  - Status changes sent as GET with query params, no CSRF token
  - Fix: Change to `fetch(url, { method: 'PUT', headers: { 'X-CSRF-TOKEN': ... }, body: ... })`

### C6. Api\TicketController::store() hardcoded default IDs

- [ ] **Api/TicketController.php** (Line 84-97) — `type_id ?? 1`, `project_id ?? 1`, etc.
  - If IDs 1 don't exist in database, inserts fail with FK violation
  - No validation that IDs exist via `exists:` rule
  - Fix: Add `exists:types,id` validation rules for all ID fields

---

## HIGH Priority Issues

### H1. Missing authorization on TicketsController::note()

- [ ] **TicketsController::note()** (Line 346-376) — No authorization check
  - Any authenticated user can change status and add notes to ANY ticket
  - Fix: Add `$this->authorize('addNote', $ticket);` after findOrFail

### H2. No validation on TicketsController::api()

- [ ] **TicketsController::api()** (Line 327) — Uses `Request` instead of Form Request
  - Only validates `status` field but no other input is sanitized
  - Fix: Create `UpdateTicketStatusRequest` or validate inline

### H3. No validation on TicketsController::fetch()

- [ ] **TicketsController::fetch()** (Line 435-438) — No validation on date inputs
  - `$request->started_at` and `$request->completed_at` passed directly to `whereBetween`
  - Fix: Add `$request->validate(['started_at' => 'required|date', 'completed_at' => 'required|date|after_or_equal:started_at'])`

### H4. No validation on ReleaseController::put()

- [ ] **ReleaseController::put()** (Line 35-58) — Uses raw `$request->title` and `$request->body`
  - No Form Request, no validation at all
  - Fix: Use `StoreReleaseRequest` or create `UpdateReleaseRequest`

### H5. No validation on MilestoneController::store() date processing

- [ ] **MilestoneController::store()** (Line 135-151) — `$request->id` compared loosely to `'new'`
  - `$request->id == 'new'` — loose comparison, `0 == 'new'` is true in PHP 7 (false in PHP 8+, but fragile)
  - Fix: Use strict comparison `$request->id === 'new'`

### H6. N+1 query in tickets/show.blade.php view

- [ ] **tickets/show.blade.php** (Line 283-287) — Inline query + N+1
  - `$ticket->views()->select(...)` runs a query in the view
  - Then `$view->user->name` triggers N+1 (user not eager loaded)
  - Fix: Move query to controller, eager load `user` relationship

### H7. N+1 in mail/notify.blade.php

- [ ] **mail/notify.blade.php** (Line 3-8) — Inline query in email template
  - `$ticket->notes()->where(...)->orderBy(...)->first()` runs a query
  - Lines 5-8: `$ticket->importance->name`, `$ticket->status->name`, etc. — N+1 if relationships not loaded
  - Fix: Eager load relationships before passing to mail view

### H8. `env()` call in view (breaks with config cache)

- [ ] **mail/notify.blade.php** (Line 1) — `env('APP_URL')` used in view
  - `env()` returns `null` when config is cached (production)
  - Fix: Use `config('app.url')` or `url()`

### H9. Missing foreign key indexes on tickets table

- [ ] **Migration: create_tickets_table** — Foreign keys commented out, no indexes
  - Columns `user_id`, `type_id`, `status_id`, `importance_id`, `milestone_id`, `project_id`, `user_id2` have NO indexes
  - All ticket queries (filtering, sorting, joins) do full table scans
  - Fix: Create new migration adding indexes to all foreign key columns

### H10. Missing foreign key indexes on notes table

- [ ] **Migration: create_notes_table** — Foreign keys commented out
  - `ticket_id` and `user_id` have no indexes
  - `getActualHoursAttribute()` calls `notes()->sum('hours')` — full scan per ticket
  - Fix: Create new migration adding indexes

### H11. Ticket::$fillable includes `user_id` and `closed_at`

- [ ] **Ticket.php** (Line 36-38) — `user_id`, `user_id2`, `closed_at` in fillable
  - These should be set server-side only, not mass-assignable
  - `claim()` line 139 calls `$ticket->update($request)` with full ticket array — could overwrite user_id
  - Fix: Remove `user_id`, `closed_at` from fillable; set explicitly in controller methods

### H12. Race condition in TicketsController::estimate()

- [ ] **TicketsController::estimate()** (Line 378-433) — No transaction wrapping
  - Between check at line 382 and save at line 426, another request could modify the same ticket
  - `Ticket::find()` called twice (lines 421-422) — redundant and non-atomic
  - Fix: Wrap in `DB::transaction()`, use single `findOrFail()`

---

## MEDIUM Priority Issues

### M1. Milestone::watchers() missing return type

- [ ] **Milestone.php** (Line 63) — `public function watchers()` has no return type
  - All other relationships have typed returns (`HasMany`, `BelongsTo`)
  - Fix: Add `: HasMany` return type

### M2. Milestone model missing date casts

- [ ] **Milestone.php** — No `casts()` method for `start_at`, `due_at`, `end_at`
  - Dates stored as strings, not Carbon objects
  - `Carbon::parse($milestone->start_at)` used manually in report() instead of automatic casting
  - Fix: Add `casts()` method with datetime casts

### M3. Release model missing date casts

- [ ] **Release.php** — No `casts()` method for `started_at`, `completed_at`
  - Manual `strtotime()` used in ReleaseController::put()
  - Fix: Add `casts()` with datetime casts, use Carbon in controller

### M4. Ticket::notifyWatchers() N+1 risk

- [ ] **Ticket.php** (Line 29-33) — Watchers loaded via `$this->watchers` in boot event
  - When a ticket is updated, `$this->watchers` triggers a query if not eager loaded
  - Each `$watcher->user` triggers another query
  - Fix: Use `$this->load('watchers.user')` at start of method

### M5. ProjectsController::show() completion logic

- [ ] **ProjectsController.php** (Line 53-59) — Dual query + wrong logic
  - Line 53: `$project->tickets()->...->count()` — executes a query
  - Line 55: `$project->tickets->count()` — loads ALL tickets into memory (different from line 53!)
  - Line 57: `if ($total !== 0 && $completed !== 0)` — won't calculate when completed=0 but total>0 (result is correct by accident since percent defaults to 0, but logic is misleading)
  - Fix: Use single query, consistent approach: `$total = $project->tickets()->count()`

### M6. UsersController time display bug

- [ ] **UsersController.php** (Line 34, 107) — `$time->format('H') > 12` misses 12:00-12:59
  - At 12:30 PM, `format('H')` returns `12`, which is NOT `> 12`, so AM/PM suffix is skipped
  - Fix: Use `>= 12` or always show 12-hour format

### M7. TicketService::changes() loose comparison

- [ ] **TicketService.php** (Line 30) — `$old[$change] != $new[$change]` uses loose comparison
  - `0 != null` is false, `'' != null` is false — could miss real changes
  - Fix: Use strict `!==` comparison

### M8. TicketService::notate() stores HTML in notes

- [ ] **TicketService.php** (Line 116-119) — Builds HTML `<ul><li>` in note body
  - Stored XSS risk if change_list values contain user input
  - These are rendered with `{!! clean(...) !!}` but better to not store raw HTML
  - Fix: Store structured data or plain text, format in view

### M9. Pagination unconstrained page size

- [ ] **TicketsController::index()** (Line 53-57) — `$perpage = (int) $request->perpage` with no max
  - User can request `?perpage=999999` and load entire table
  - Fix: `$perpage = min(max((int) $request->get('perpage', 10), 1), 100);`

### M10. MilestoneController::report() burndown bug

- [ ] **MilestoneController::report()** (Line 261-270) — `->get()` called on filtered collection
  - `$tickets->whereIn('status_id', $closedStatusIds)->get()` — `get()` is not valid on Laravel Collection (it's for query builder)
  - This will either error or return unexpected results
  - Fix: Remove `->get()`, use `$tickets->whereIn(...)` directly (it's already a Collection)

### M11. Missing foreign key indexes on remaining tables

- [ ] **Migrations** — ticket_user_watchers, ticket_estimates, releases tables missing indexes
  - `ticket_user_watchers`: needs indexes on `ticket_id`, `user_id`
  - `ticket_estimates`: needs indexes on `ticket_id`, `user_id`
  - `releases`: needs index on `user_id`
  - Fix: Create single migration adding all missing indexes

### M12. StoreMilestoneRequest `id` validation concern

- [ ] **StoreMilestoneRequest.php** (Line 17) — Validates `id` as `required|string`
  - The `id` field is used to determine create vs update, not a database ID
  - Mix of routing concern and request data — confusing, error-prone
  - Fix: Use separate routes for create and update (RESTful), or rename to `action`

### M13. All policies return `true` for create/view

- [ ] **MilestonePolicy, ProjectPolicy** — All methods return `true`
  - These policies provide no actual authorization — anyone can do anything
  - `MilestonePolicy::update()` returns `true` — no owner check
  - `ProjectPolicy::update()` returns `true` — no owner check
  - Fix: Implement actual business rules (e.g., only owner/scrummaster can update milestone)

### M14. Ticket claim() mass assignment via update()

- [ ] **TicketsController::claim()** (Line 133-139) — `$ticket->update($request)` with full array
  - `$ticket->toArray()` is modified then passed to `update()` — includes ALL ticket fields
  - Could inadvertently reset fields if model attributes change between toArray and update
  - Fix: Only update the specific field: `$ticket->update(['user_id2' => Auth::id()])`

### M15. N+1 queries in view loops (list, milestone, projects)

- [ ] **tickets/list.blade.php** (Line 124) — `$tick->notes()->where(...)->count()` per row
  - Runs a DB query per ticket in the list
  - Fix: Eager load note counts in controller with `withCount`

- [ ] **milestone/show.blade.php** (Line 53, 58) — `$milestone->tickets()->where('status_id', $code_id)->count()` per status
  - Runs 2 queries per status code in the loop (count on line 53, count again on line 58)
  - Fix: Group ticket counts by status_id in controller, pass as array

- [ ] **projects/index.blade.php** (Line 31, 35) — `$project->tickets()->whereIn(...)->count()` per project
  - Runs 2 queries per project row
  - Fix: Use `withCount` in controller

### M16. Hardcoded status IDs in views

- [ ] **milestone/print.blade.php** (Line 37) — `->whereNotIn('status_id', [8, 9])` hardcoded
  - Inconsistent with `Status::closedStatusIds()` used elsewhere
  - Fix: Use `Status::closedStatusIds()` consistently

- [ ] **projects/index.blade.php** (Line 31) — `whereIn('status_id', ['1','2','3','6'])` hardcoded as strings
  - Magic numbers, also passes strings instead of integers
  - Fix: Create `Status::openStatusIds()` method, use integers

- [ ] **tickets/create.blade.php** (Lines 42, 52, 62, 72, 82) — Default select values hardcoded
  - `old('type_id', 3)`, `old('importance_id', 2)`, `old('project_id', 4)` etc.
  - If these IDs don't exist, wrong default is selected
  - Fix: Pass defaults from controller config, not hardcoded in view

### M17. Legacy PHP tags in home.blade.php

- [ ] **home.blade.php** (Lines 8-14) — Raw `<?php if(...){ ?>` instead of Blade
  - Also uses Bootstrap 3 classes (`panel panel-default`)
  - Fix: Convert to `@if` / `@endif` with Bootstrap 5 classes

### M18. Migration rollback issues

- [ ] **releases migration down()** — `Schema::dropIfExists('release')` should be `'releases'`
  - Rollback will silently fail — table name doesn't match
  - Fix: Change to `'releases'`

- [ ] **10+ migrations have empty down()** methods
  - `ticket_estimates`, `release_tickets (2021_09_08)`, `modify_milestone_data`, `add_user_permissions`, `enhanceuser`, `addmode`, `modify_ticket_storypoints`, `add_importance_icons`, `add_type_icons`, `modify_note_types`, `add_ticket_time`, `add_note_time`, `modify_milestone_assignments`, `add_ticket_actualtime`
  - Migrations cannot be rolled back
  - Fix: Implement proper down() methods for each

### M19. ImportController double transaction

- [ ] **ImportController::create()** (Lines 23-38) — `DB::beginTransaction()` wraps `DB::transaction()` inside Importer
  - Importer.php line 28 already uses `DB::transaction()` — nested transactions are redundant
  - Fix: Remove outer `DB::beginTransaction/commit/rollBack` in ImportController, rely on Importer's transaction

---

## LOW Priority Issues

- [ ] **L1.** `View()` uppercase in TicketsController::home() line 48 and multiple ReleaseController/UsersController methods — should be `view()`
- [ ] **L2.** No return type declarations on any controller methods — Laravel 12 best practice
- [ ] **L3.** `date()` and `strtotime()` used instead of Carbon in TicketsController::clone/edit lines 181-185, TicketService lines 67-76
- [ ] **L4.** No SoftDeletes on any model — data permanently deleted
- [ ] **L5.** Cache invalidation missing — `getLookups()` cached 60 min, never invalidated when types/projects change
- [ ] **L6.** Release/show.blade.php: `{!! json_encode() !!}` in milestone/report.blade.php lines 264-266 — use `@json()` directive
- [ ] **L7.** Inline JavaScript in 11+ blade views (Quill editor setup) — should extract to JS files
- [ ] **L8.** `$ticket->actual = $ticket->actual_hours` in note() line 371 — writes computed value back to DB column on every note add
- [ ] **L9.** `*.md` in .gitignore blocks all markdown — overly broad, only todo.md should be listed
- [ ] **L10.** Watcher notifications fire synchronously in model boot — should be queued for performance
- [ ] **L11.** `dangerouslyPasteHTML()` used in Quill initialization across 10 views — content comes from DB, mitigated by server-side `clean()` but worth documenting
- [ ] **L12.** `onclick` handlers in tickets/show.blade.php lines 107, 129 — should use `addEventListener` pattern

---

## Laravel Best Practices Checklist

- [ ] Use route model binding consistently
- [x] ~~Add Form Request validation to all methods~~ (mostly done, ReleaseController::put still missing)
- [x] ~~Use policies for authorization~~ (created but policies need real business logic)
- [ ] Eager load all relationships (N+1 still in show.blade.php view queries and mail template)
- [x] ~~Use database transactions for multi-step operations~~
- [x] ~~Sanitize all user input before output~~ (HTMLPurifier via `clean()`)
- [ ] Use type hints and return types on all controller methods
- [x] ~~Use `::class` instead of string references~~
- [x] ~~Use method-style `casts(): array`~~
- [ ] Add indexes for frequently queried columns (critical — no foreign key indexes)

---

## Completed Fixes (2026-03-21)

- Reference assignment bug
- File upload security
- Mass assignment in TicketsController
- N+1 queries in home() and board()
- strtotime on null dates
- Empty string comparison
- Division by zero
- $sp undefined variable
- Cache lookups()
- Remove jQuery, use HTML5 date inputs
- Lazy load Quill editor
- Split CSS into chunks
- Remove Tailwind CSS
- Model relationship fixes (Release, Milestone, Note)
- ReleaseController dates (null vs empty string)
- Project completion logic inversion
- UsersController N+1 and validation
- Pagination on board() and API
- $casts on Ticket, User models
- Status::closedStatusIds() method
- TrustProxies env configuration
- API token stateless auth
- ReleaseTicket migration
- Session encryption and SameSite cookies
- bcrypt rounds 10->12
- Health check endpoints in maintenance mode
- Importer moved to Services
- Dead code removal (Sprint, Watcher models)
- Comments cleanup
- Laravel 11/12 bootstrap modernization
- Form Request classes (Store, Update, Estimate)
- Authorization Policies (Ticket, Milestone, Release, Project)
- Config file updates to Laravel 12 style
- Laravel 12 framework upgrade (v11.50.0 → v12.55.1)
- Deleted 17 legacy files (Kernel, Handler, middleware, providers, server.php, webpack.mix.js)
- Constructor middleware removed from all controllers (route-level in L12)
- Policy auto-discovery (AuthServiceProvider removed)
- `casts(): array` method on Ticket, User, Note, Milestone, TicketEstimate models
- `::class` syntax on all models
- `HasFactory` trait on Ticket, User, Note, Milestone, Project models
- `'password' => 'hashed'` cast on User model
- Division by zero guards (TicketsController::estimate, MilestoneController::getShow)
- Importance, Type, Status already have `$timestamps = false`
- State-changing GET requests converted to POST (claim, watch, notes/hide)
- Duplicate watch functionality removed
- Route parameter consistency fixes
- BatchUpdateTicketRequest for batch updates
- Api\TicketController IDOR fix (user scope filter)
- NotesController::hide() ownership verification
- Release model datetime casts (StoreReleaseRequest)
- Project model boolean cast for active
- TicketView unique index migration
- Ticket::actual cast fix (integer)
- Ticket::actual_hours N+1 optimization with withSum
- Importer service: transaction, file handle, bounds checking
- Fixed typo "Aisa" -> "Asia"
- XSS protection via HTMLPurifier `clean()` on all raw output
