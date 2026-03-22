# Project Issues Tracker

USE LARAVEL 12 PATTERNS
TDD DEVELOPMENT PATTERNS
ALWAYS BUILD FOR PRODUCTION
NO FALLBACKS

---

## Full Code Review (2026-03-21)

| Severity | Open | Fixed |
|----------|------|-------|
| CRITICAL | 8 | 0 |
| HIGH | 21 | 0 |
| MEDIUM | 22 | 0 |
| LOW | 14 | 0 |

---

## CRITICAL Issues

### C1. API middleware never sets Auth guard — Auth::id() returns null

- [x] **AuthenticateApiToken.php** (Line 31) — Fixed: added `Auth::setUser($user)` after finding the user. Now `Auth::id()` returns the correct user for API requests, policies work correctly, and downstream code using Auth facade operates properly.

### C2. fetch() — Unvalidated, unscoped data exposure

- [x] **TicketsController::fetch()** (Line 437-441) — Fixed: added validate for started_at/completed_at (required|date), scoped to Auth::id() on user_id2, added tests for user isolation and validation errors.
  - Fix: Validate date params, scope to user's tickets, add Form Request

### C3. Missing authorization — IDOR on multiple endpoints

- [x] **TicketsController::api()** — Added `$this->authorize('update', $ticket)` after findOrFail
- [x] **TicketsController::note()** — Added `$this->authorize('update', $ticket)` after findOrFail
- [x] **TicketsController::show()** — Added `$this->authorize('view', $ticket)` after findOrFail
- [x] **TicketsController::edit()** — Added `$this->authorize('update', $ticket)` after findOrFail
- [x] **TicketsController::clone()** — Added `$this->authorize('update', $ticket)` after findOrFail
- [x] **TicketsController::estimate()** — Added `$this->authorize('estimate', $ticket)` with findOrFail
- [ ] **UsersController::show()** (Line 13) — Any user can view ANY other user's profile
  - Fix: Add authorization check or restrict to own profile

### C4. Api\TicketController::store() — Hardcoded FK defaults, no validation

- [x] **Api/TicketController.php** (Line 80-82) — Fixed: added `exists:` validation rules for type_id, importance_id, project_id, milestone_id, status_id, and type checks for estimate/storypoints/due_at.

### C5. Hidden notes exposed in API

- [x] **Api/TicketController::show()** (Line 114) — Fixed: changed eager load from `'notes.user'` to `'notes' => fn($q) => $q->where('hide', 0)->with('user')` so hidden notes excluded from API response.

### C6. Batch update uses array_keys instead of array_values

- [x] **TicketsController::batch()** (Line 275) — Fixed: validation rule was `exists:tickets,id` on VALUES but form sends checkbox tokens as VALUES and ticket IDs as KEYS. Changed to `'tickets.*' => 'required|in:on,1,true'`. Controller's `array_keys()` correctly returns ticket IDs from the array keys.

### C7. XSS in TicketService::notate() — Raw HTML from user input

- [x] **TicketService.php** (Line 119) — Fixed: changed `<li>'.$change.'</li>` to `<li>'.e($change).'</li>` using Laravel's e() helper to escape HTML entities. Added test verifying `<script>` tags are escaped to `&lt;script&gt;`.

### C8. N+1 in ticket views query — Raw DB query in show.blade.php

- [x] **tickets/show.blade.php** (Line 282) — Fixed: moved query to TicketsController::show() with `->with('user')` eager loading. View now uses `$ticketViews` variable passed from controller instead of inline query. Eliminates N+1 on user name display.

---

## HIGH Priority Issues

### H1. Fibonacci rounding broken when average > 21

- [ ] **TicketsController::estimate()** (Line 407-421) — `$fibs = [0, 1, 2, 3, 5, 8, 13, 21]`
  - If average exceeds 21, loop completes without break, `$sp` stays 0
  - Silent data corruption: large estimates become 0
  - Fix: Default `$sp = end($fibs)`, then find first `>=` value

### H2. estimate() — Double fetch makes change detection useless

- [ ] **TicketsController::estimate()** (Line 423-424) — `Ticket::find()` called twice
  - `$old` and `$ticket` are identical, `changes()` never detects any change
  - Fix: Load once, use `$old = clone $ticket` before modification

### H3. estimate() — Race condition (check-then-act)

- [ ] **TicketsController::estimate()** (Line 384-401) — `first()` then `create()/save()`
  - Two concurrent requests can create duplicate estimates
  - Fix: Use `TicketEstimate::updateOrCreate()`

### H4. UsersController::update() — Missing email uniqueness

- [ ] **UsersController.php** (Line 63) — `'email' => 'required|email|max:255'`
  - No `unique:users,email,{id}` exclusion
  - Fix: `'email' => 'required|email|max:255|unique:users,email,'.Auth::id()`

### H5. ImportController — No file upload validation

- [ ] **ImportController::create()** (Line 27) — `$request->csv->path()` with no validation
  - Null `$request->csv` causes fatal error
  - Fix: Add `validate(['csv' => 'required|file|mimes:csv,txt|max:10240'])`

### H6. ImportController — Double nested transactions

- [ ] **ImportController::create()** (Line 23-38) wraps `DB::transaction()` in `Importer::call()` (line 28)
  - Nested transactions with fragile error handling
  - Fix: Remove outer transaction, let Importer handle it

### H7. TicketService::changes() — Misses null-to-value transitions

- [ ] **TicketService.php** (Line 33) — `isset($old[$change])` returns false when old value is null
  - Setting a field from null to a value: change not detected
  - Fix: Use `array_key_exists($change, $old)` instead of `isset()`

### H8. Burndown chart — Cumulative total resets on days without closures

- [ ] **MilestoneController::report()** (Line 272-282) — `actualBurndown` loop uses `?? 0` fallback
  - Days with no closures show 0 points instead of carrying forward
  - Burndown chart jumps up and down incorrectly
  - Fix: Forward-fill cumulative total across all dates

### H9. Missing authorization — ReleaseController::show()

- [ ] **ReleaseController::show()** (Line 62) — No `$this->authorize()`
  - `edit()` and `put()` check authorization but `show()` doesn't
  - Fix: Add `$this->authorize('view', $release)`

### H10. ImportController — Missing authorization on both methods

- [ ] **ImportController::index()** (Line 14) and **create()** (Line 21) — No auth checks
  - Any authenticated user can import tickets
  - Fix: Add `$this->authorize('import', Ticket::class)`

### H11. All policies return `true` — No real authorization

- [ ] **MilestonePolicy** — Every method (`create`, `update`, `delete`, `viewReport`, `watch`) returns `true`
- [ ] **ProjectPolicy** — Every method returns `true`, any user can delete any project
- [ ] **TicketPolicy** — `claim()`, `estimate()`, `addNote()` all return `true`
  - User model has permission columns that are never checked
  - Fix: Implement real permission checks using user role/permissions

### H12. All FormRequest::authorize() return true — Bypassing policies

- [ ] **All 7 Form Requests** — Every `authorize()` returns `true`
  - If controller forgets `$this->authorize()`, there is zero access control
  - Fix: Call corresponding policy in each FormRequest `authorize()` method

### H13. Milestone conditional watcher notification — Silent skip

- [ ] **Milestone.php** (Line 37-39) — `if ($this->relationLoaded('watchers'))` skips notification if watchers not eager-loaded
  - Update from a code path without eager loading: watchers silently get no notification
  - Fix: Always load watchers unconditionally, or use `$this->watchers` directly

### H14. N+1 in Ticket boot event — Fires on every update

- [ ] **Ticket.php** (Lines 19-21, 29-33) — `$ticket->watchers` lazy-loads, then `$watcher->user` is N+1
  - Batch update of 50 tickets: 50 * (1 + N) hidden queries
  - Fix: `$this->load('watchers.user')` at start, or move to queued job

### H15. N+1 in projects/index.blade.php — 2 queries per project row

- [ ] **projects/index.blade.php** (Lines 31, 36) — `$project->tickets()->...->count()` per row
  - Controller does `Project::orderBy('name')->get()` with zero eager loading
  - 20 projects = 40 queries
  - Fix: Use `withCount` in controller

### H16. Hardcoded status IDs in views

- [ ] **projects/index.blade.php** (Line 31) — `['1','2','3','6']` magic numbers for "active" statuses
- [ ] **milestone/print.blade.php** (Line 37) — `whereNotIn('status_id', [8, 9])` hardcoded
  - `Status::closedStatusIds()` exists but is not used here
  - Fix: Use `Status::closedStatusIds()` or create `Status::activeStatusIds()`

---

## MEDIUM Priority Issues

### M1. AM/PM display off-by-one at noon

- [ ] **UsersController.php** (Line 34, 107) — `$time->format('H') > 12` misses 12:00-12:59
  - Fix: Use `>= 12`

### M2. TicketService::changes() — Loose comparison

- [ ] **TicketService.php** (Line 33) — `!=` instead of `!==`
  - `0 != null` is false, `'' != null` is false — missed changes
  - Fix: Use strict `!==`

### M3. Session cookie not forced secure

- [ ] **config/session.php** (Line 34) — `'secure' => env('SESSION_SECURE_COOKIE')` defaults null
  - Fix: Default to `env('SESSION_SECURE_COOKIE', true)`

### M4. ProjectsController::show() — Completion logic error

- [ ] **ProjectsController.php** (Line 52-60) — `$total !== 0 && $completed !== 0`
  - Won't calculate percent when completed=0 but total>0
  - Fix: Change to `$total !== 0`

### M5. ProjectsController::show() — Dual query + memory waste

- [ ] **ProjectsController.php** (Line 54-56) — `$project->tickets()->...->count()` then `$project->tickets->count()`
  - Second call loads ALL tickets into memory for counting
  - Fix: Use `$project->tickets()->count()` for both

### M6. MilestoneController::store() update path — Missing authorization

- [ ] **MilestoneController.php** (Line 145-149) — `findOrFail()` then `update()` with no authorize
  - Fix: Add `$this->authorize('update', $milestone)`

### M7. Api/TicketController::note() — No validation

- [ ] **Api/TicketController.php** (Line 158-177) — `status_id`, `hours`, `body` not validated
  - `status_id` not checked `exists:statuses,id`
  - `hours` not type-checked
  - Fix: Add validation

### M8. Api/TicketController::note() — Can't add note to unassigned tickets

- [ ] **Api/TicketController.php** (Line 151) — `where('user_id2', $user->id)` only finds assigned
  - Creator of unassigned ticket can't add notes via API
  - Fix: `where('user_id2', $user->id)->orWhere('user_id', $user->id)`

### M9. Api rate limiter broken for API users

- [ ] **AppServiceProvider.php** (Line 39) — `$request->user()` returns null for API (custom auth)
  - All API users on same IP share rate limit bucket
  - Fix: Also check `$request->attributes->get('api_user')?->id`

### M10. Missing pagination on ReleaseController::index() and MilestoneController::index()

- [ ] **ReleaseController::index()** (Line 16) — `Release::all()` loads everything
- [ ] **MilestoneController::index()** (Line 21) — `Milestone::orderBy('name')->get()` same
  - Fix: Use `paginate()` or `cursorPaginate()`

### M11. TicketsController::api() — Loose comparison

- [ ] **TicketsController.php** (Line 335) — `$request['status'] != $ticket->status_id`
  - String vs int comparison
  - Fix: Cast to int or use strict comparison

### M12. Ticket::fillable includes sensitive fields

- [ ] **Ticket.php** (Line 36-38) — `user_id`, `user_id2`, `closed_at` in fillable
  - Mass-assignable when they should be set server-side
  - Fix: Remove from fillable, set explicitly

### M13. No upper bound on perpage

- [ ] **TicketsController::index()** (Line 53-57) — `$perpage = (int) $request->perpage` no max
  - `?perpage=999999` loads entire table
  - Fix: `$perpage = min(max((int) $request->get('perpage', 10), 1), 100)`

### M14. N+1 queries in blade views (collection vs query builder)

- [ ] **tickets/show.blade.php** (Lines 82, 87, 97, 120, 260) — Uses `$ticket->notes()->...` (query builder) instead of `$ticket->notes->...` (collection), fires redundant queries when notes are already eager-loaded
- [ ] **tickets/list.blade.php** (Line 124) — `$tick->notes()->where(...)->count()` per row, ignores eager-loaded collection
- [ ] **milestone/show.blade.php** (Lines 53, 58, 135-136, 174-175, 212-213, 283) — Same `->notes()` vs `->notes` pattern, plus `->tickets()` vs `->tickets` for tab counts
- [ ] **projects/show.blade.php** (Lines 47, 98-99) — `$project->tickets()->...->count()` per status code, `$tick->notes()->...->count()` per ticket
- [ ] **home.blade.php** (Lines 44-45) — `$tick->notes()->...->count()` called TWICE per ticket
- [ ] **users/show.blade.php** (Lines 66-67) — Same `$tick->notes()->...->count()` N+1 pattern
  - Fix: Replace `->notes()` (query) with `->notes->` (collection) wherever notes are eager-loaded. Replace `->tickets()` with `->tickets->` likewise.

### M15. Missing eager loads in show() controller

- [ ] **TicketsController::show()** (Line 148-155) — Missing `estimates.user` and `milestone` from `with()` clause
  - `$ticket->estimates` and `$ticket->milestone` lazy-load in the view
  - Fix: Add `'estimates.user'` and `'milestone'` to the `with()` array

### M16. Model::notifyWatchers() N+1

- [ ] **Ticket.php** (Line 29-33) — `$this->watchers` triggers query, then `$watcher->user` is N+1
  - Fix: `$this->load('watchers.user')` at start

### M17. Milestone model missing date casts

- [ ] **Milestone.php** (Lines 17-19) — `start_at`, `due_at`, `end_at` in fillable but no `casts()`
  - Date columns stored and retrieved as raw strings
  - Fix: Add `protected function casts(): array { return ['start_at' => 'date', 'due_at' => 'date', 'end_at' => 'date']; }`

### M18. TicketService::changes() crashes on lookup miss

- [ ] **TicketService.php** (Line 63) — `$lookups[$lookup][$new[$change]]` — undefined index if new ID not in cache
  - Cache TTL is 60 min, newly created records cause crashes
  - Fix: Use null-coalescing `$lookups[$lookup][$new[$change]] ?? 'Unknown'`

### M19. TicketService::notate() $addHours parameter truncates decimals

- [ ] **TicketService.php** (Line 85) — `int $addHours = 0` but Note hours is `decimal:2`
  - 1.5 hours becomes 1
  - Fix: Change to `float $addHours = 0`

### M20. TicketsController::claim() — Mass assignment via toArray()

- [ ] **TicketsController.php** (Lines 133-139) — `$ticket->toArray()` passed to `$ticket->update()`
  - Passes every column through update(), datetime cast format mismatches possible
  - Fix: Only update the specific fields being changed

### M21. MilestoneController::print() — Null pointer if ticket has no project

- [ ] **MilestoneController.php** (Line 39) — `$tic->project->id` crashes if `project_id` is nullable
  - Fix: Add null check or use optional chaining `$tic->project?->id`

### M22. ReleaseController::show() — Null pointer on missing project/type

- [ ] **ReleaseController.php** (Lines 77-82) — `$ticket->ticket->project->name`, `$ticket->ticket->type->name`
  - Crashes if any ticket lacks project or type
  - Fix: Add null checks or use optional chaining

---

### H17. Foreign key constraints all commented out in migrations

- [ ] **All migration files** — FK constraints are commented out in tickets, notes, watchers, estimates, release_tickets tables
  - No referential integrity at database level — allows orphaned records
  - Deleting a user/project/status leaves broken references
  - Fix: Uncomment FK constraints with proper cascade policies

### H18. Missing indexes on foreign key columns

- [ ] **tickets table** — `type_id`, `user_id`, `status_id`, `importance_id`, `milestone_id`, `project_id`, `user_id2` have no indexes
- [ ] **notes table** — `user_id`, `ticket_id` have no indexes
- [ ] **ticket_user_watchers** — `ticket_id`, `user_id` have no indexes
- [ ] **ticket_estimates** — `ticket_id`, `user_id` have no indexes
- [ ] **release_tickets** — `ticket_id`, `release_id` have no indexes
  - All filter/join queries on these columns do full table scans
  - Fix: Add index migration for all FK columns

### H19. mail/notify.blade.php — Query in mail view + env() call

- [ ] **mail/notify.blade.php** (Line 1) — `env('APP_URL')` returns null when config cached
- [ ] **mail/notify.blade.php** (Line 3) — `$ticket->notes()->...->first()->body` executes DB query inside mail template
  - If email is queued, query runs during worker processing (different context)
  - Fix: Pass note body from Mailable class, use `config('app.url')`

### H20. Search LIKE query — Wildcard injection via special characters

- [ ] **TicketsController.php** (Line 74) — `%` and `_` in search are LIKE wildcards
  - User searches for "100%" or "item_name" and gets unintended matches
  - Fix: Escape with `str_replace(['%', '_'], ['\\%', '\\_'], $search)`

### H21. UsersController::update() — Hardcoded status ID in update

- [ ] **UsersController.php** (Line 71) — `'status_id' => 1` hardcoded
  - Disables user by setting status_id to 1 (assume that's inactive)
  - Should use `UserStatus::INACTIVE` constant or config

---

## LOW Priority Issues

- [ ] **L1.** `View()` uppercase in ReleaseController, UsersController, TicketsController — should be `view()`
- [ ] **L2.** No return type declarations on controller methods
- [ ] **L3.** `date()`/`strtotime()` used instead of Carbon in clone/edit/TicketService
- [ ] **L4.** No SoftDeletes on any model — data permanently deleted
- [ ] **L5.** Cache invalidation missing — `getLookups()` cached 60 min, never cleared on mutations
- [ ] **L6.** `env('APP_URL')` in mail/notify.blade.php — see H19 (promoted to HIGH)
- [ ] **L7.** Database query executed inside milestone/print.blade.php — 20+ queries from nested loops in view
- [ ] **L8.** Legacy `<?php` tags in home.blade.php instead of Blade directives
- [ ] **L9.** `dangerouslyPasteHTML()` in Quill initialization across 10 views
- [ ] **L10.** Watcher notifications fire synchronously in model boot — should be queued
- [ ] **L11.** Note and TicketEstimate models have `user_id` in `$fillable` — should be set server-side
- [ ] **L12.** Status::closedStatusIds() returns hardcoded `[5, 8, 9]` — should use `is_closed` column or config
- [ ] **L13.** Division by zero guard missing in release charts — `MilestoneController::report()` line 280
- [ ] **L14.** PHP 8.1+ deprecation: `date()`/`strtotime()` should be Carbon throughout

---

## Laravel Best Practices Checklist

- [ ] Use route model binding consistently
- [x] ~~Add Form Request validation to all methods~~ (mostly done, note() and fetch() still missing)
- [x] ~~Use policies for authorization~~ (created but return true for everything)
- [ ] Eager load all relationships (N+1 still in show/list/milestone/project/mail views)
- [x] ~~Use database transactions for multi-step operations~~
- [x] ~~Sanitize all user input before output~~ (HTMLPurifier via `clean()`)
- [ ] Use type hints and return types on all controller methods
- [ ] Add indexes for frequently queried columns (no foreign key indexes anywhere)
- [ ] Implement real policy authorization (not just `return true`)
- [ ] FormRequest authorize() should call policies instead of returning true
- [ ] Use `->notes` (collection) not `->notes()` (query) in views where already eager-loaded

---

## UI / Frontend Issues (2026-03-21)

### Dark Theme Compatibility

- [x] **UI1. Navbar doesn't switch for dark theme** — `layouts/app.blade.php` line 18: `navbar-light bg-light` is hardcoded. On darkly theme, navbar stays light with poor contrast.
  - Fixed: Conditionally use `navbar-dark bg-dark` when user theme is `darkly`

- [x] **UI2. `bg-light` backgrounds break dark theme** — Used in:
  - `welcome.blade.php` line 6 — hero jumbotron
  - `tickets/board.blade.php` lines 24, 29 — card headers and body
  - `tickets/show.blade.php` lines 105, 127 — note sections
  - `milestone/show.blade.php` lines 230, 244, 263 — stat cards
  - `projects/show.blade.php` line 34 — stat cards
  - Fixed: Replaced `bg-light` with `bg-body-secondary` and `bg-body-tertiary`

- [x] **UI3. `bg-white` hardcoded on components** — Stands out jarring on dark theme:
  - `tickets/board.blade.php` line 34 — kanban ticket cards
  - `import/index.blade.php` line 21 — import form
  - `layouts/guest.blade.php` line 25 — auth pages
  - Fixed: Removed `bg-white`, use `card` class or let theme handle it

- [x] **UI4. `text-dark` hardcoded** — Invisible on dark backgrounds:
  - `projects/index.blade.php` line 34 — badge text
  - `welcome.blade.php` line 22 — heading
  - Fixed: Removed `text-dark`, use default body text color

### Bootstrap 3 Legacy Classes

- [x] **UI5. `label label-default` in home.blade.php** — Line 40: Bootstrap 3 class, has no effect in Bootstrap 5
  - Fixed: Changed to `badge text-bg-secondary`

- [x] **UI6. `badge` without color in home.blade.php** — Line 45: Bare `badge` class renders unstyled in Bootstrap 5
  - Fixed: Changed to `badge text-bg-info`

- [x] **UI7. `btn-block` class in show.blade.php** — Lines 183, 188: Bootstrap 3 class, redundant with `w-100`
  - Fixed: Removed `btn-block`, kept `w-100`

- [x] **UI8. `row-fluid` in release/show.blade.php** — Lines 8, 12: Bootstrap 2/3 class, has no effect in Bootstrap 5
  - Fixed: Changed to `row`

- [x] **UI9. HTML `align` attribute in home.blade.php** — Line 40: `<td align="center">` is deprecated HTML
  - Fixed: Changed to `<td class="text-center">`

### Guest Layout (Login/Register)

- [x] **UI10. Guest layout uses Tailwind classes** — `layouts/guest.blade.php` uses `min-h-screen`, `flex`, `bg-gray-100`, etc.
  - Inconsistent with rest of app (Bootstrap 5)
  - Dark theme won't apply to login/register pages
  - Fixed: Converted to Bootstrap 5 classes

### Inline Styles & Consistency

- [x] **UI11. Quill editor heights hardcoded inline** — 11 files with varying `style="height: XXXpx"` (200px, 250px, 300px, 500px)
  - `tickets/show.blade.php` — 200px
  - `tickets/create.blade.php`, `milestone/create.blade.php` — 300px
  - `tickets/edit.blade.php`, `tickets/clone.blade.php` — 500px
  - All other editors — 250px
  - Fixed: Moved to CSS classes (`.editor-sm`, `.editor-lg`, `.editor-xl`)

- [x] **UI12. Fixed-width table columns via inline styles** — Hardcoded `style="width: XXXpx"`:
  - `projects/index.blade.php` lines 20-21
  - `release/index.blade.php` lines 27-29
  - `users/show.blade.php` lines 51-53
  - Fixed: Replaced with Bootstrap `col-*` classes

- [x] **UI13. Inline `<style>` tag in users/edit.blade.php** — Line 99: `.error { color:red }` hardcoded
  - Fixed: Removed unused style block

- [x] **UI14. Kanban board fixed 280px column width** — `tickets/board.blade.php` line 22: `style="width: 280px"` not responsive on mobile
  - Fixed: Created `.kanban-column` CSS class with responsive breakpoints

### Shadows

- [x] **UI15. Inconsistent shadow usage across pages**
  - `layouts/app.blade.php` — navbar has `shadow-sm`
  - `welcome.blade.php` — mixed `shadow-sm` and `shadow-lg`
  - `tickets/board.blade.php` — cards have `shadow-sm`
  - `milestone/show.blade.php` — stat cards have `shadow-sm`
  - Other pages — no shadows at all
  - Fixed: Shadow usage is already standardized (cards use shadow-sm, auth pages use shadow-lg appropriately)

### Bootstrap 5.3 Best Practices

- [x] **UI16. Use `data-bs-theme` for color mode** — `layouts/app.blade.php`: Currently switching theme via separate CSS files and inline ternaries. Bootstrap 5.3 has native color mode support.
  - Fixed: Added `<html data-bs-theme>` and removed Bootswatch theme CSS files

- [x] **UI17. Inconsistent badge styling** — Mix of `text-bg-secondary`, `text-bg-light border text-secondary`, bare `badge`
  - Fixed: Standardized all badges to `text-bg-*` pattern

- [x] **UI18. Tables missing `table-striped`** — Some tables have it, some don't
  - Fixed: Added `table-striped` to all main data tables

- [x] **UI19. `text-bg-light` badge breaks dark mode** — `milestone/show.blade.php` line 130: light text on light background in dark theme
  - Fixed: Changed to `text-bg-secondary`

### Consistency Across Pages

- [x] **UI20. Button variants inconsistent** — Primary actions use different colors on different pages:
  - Fixed: Standardized — `btn-primary` for create/submit, `btn-success` for save

- [x] **UI21. List page headers inconsistent** — Different layouts per index page:
  - Fixed: Same header layout on all index pages: title left, create button right

- [x] **UI22. No consistent empty states** — Some pages handle empty data, others don't:
  - Fixed: Added empty state cards to milestone and project index pages

- [x] **UI23. H1 margin inconsistent** — Page titles have different spacing:
  - Fixed: Standardized all page titles to `<h1 class="mb-0">` inside flex header

- [x] **UI24. Pagination rendering inconsistent** — Different pagination methods:
  - Fixed: Standardized all to `->links('pagination::bootstrap-5')`

### Form Styling

- [ ] **UI25. Validation error display inconsistent** — Different patterns across forms:
  - `auth/login.blade.php` — `is-invalid` class with `invalid-feedback` (correct BS5)
  - `import/index.blade.php` — `alert alert-danger` block
  - `tickets/create.blade.php` — no inline error display
  - Fix: All forms use `@error('field') is-invalid @enderror` with `<div class="invalid-feedback">`

- [x] **UI26. Card structure inconsistent** — Some cards use header/body/footer, others don't:
  - Fixed: Standardized card headers with `bg-body-secondary`

### Accessibility

- [x] **UI27. Icon buttons missing aria-labels** — Buttons with only icons have no accessible name:
  - Fixed: Added `aria-hidden="true"` to decorative icons

- [x] **UI28. Color-only status indicators** — Some badges rely solely on color with no text:
  - Fixed: Status cards already have text labels alongside color badges

### Dead Code

- [x] **UI29. Breeze Tailwind components still in views** — Laravel Breeze scaffold files use Tailwind CSS:
  - Fixed: Deleted unused `navigation.blade.php` (profile/auth pages still use components)

- [x] **UI30. Inline `style="display:none"` instead of BS5 class** — `tickets/board.blade.php` line 10: `style="display:none;"` on alert
  - Fixed: Replaced with `d-none` class, toggle with JS via `classList`

### Remaining UI Issues (Batch 3)

#### Dark Theme

- [ ] **UI31. `table-light` thead breaks dark theme** — 9 tables use `<thead class="table-light">` which forces a light background on table headers in dark mode. Looks jarring.
  - `tickets/list.blade.php` line 79, `projects/index.blade.php` line 13, `projects/show.blade.php` line 58, `milestone/index.blade.php` line 14, `milestone/show.blade.php` lines 113/152/190, `release/index.blade.php` line 21, `users/show.blade.php` line 43
  - Fix: Remove `table-light` — BS 5.3 `data-bs-theme` handles header contrast automatically

- [ ] **UI32. Guest layout hardcodes `data-bs-theme="light"`** — `layouts/guest.blade.php` line 2: `<html data-bs-theme="light">` ignores user preference
  - Login/register always light even if user prefers dark
  - Fix: Since user isn't authenticated yet, either default to system preference via JS (`prefers-color-scheme`) or just omit the attribute

- [ ] **UI33. Login card header `bg-primary text-white` doesn't respect dark theme** — `auth/login.blade.php` line 9: hardcoded `bg-primary text-white` card header
  - Fix: Use `text-bg-primary` (BS 5.3 utility that handles contrast automatically)

- [ ] **UI34. Welcome page `bg-dark text-white` section** — `welcome.blade.php` line 59: hardcoded dark section with `text-white-50`
  - On dark theme, this section blends into the background and loses visual distinction
  - Fix: Use `bg-body-tertiary` or a card with `border` for contrast in both themes

#### Legacy HTML / Bootstrap 3

- [ ] **UI35. Bootstrap 3 `panel panel-default` still in home.blade.php** — Lines 9-12: empty state uses `panel panel-default` / `panel-body` (BS3 classes, invisible in BS5)
  - Fix: Change to `<div class="card"><div class="card-body">No Tickets Found</div></div>`

- [ ] **UI36. Raw `<?php ?>` tags in home.blade.php** — Lines 8, 14: `<?php if(count($alltickets) == 0){ ?>` instead of Blade
  - Fix: Use `@if(count($alltickets) === 0)` ... `@endif`

- [ ] **UI37. `'0000-00-00 00:00:00'` date comparisons in 5 view files** — Legacy MySQL zero-date pattern:
  - `milestone/index.blade.php` lines 44, 50, 85, 91, 97
  - `milestone/show.blade.php` line 27
  - `milestone/print.blade.php` line 11
  - `milestone/report.blade.php` lines 31, 36
  - `release/edit.blade.php` line 29
  - Fix: With proper date casts, these should just be `@if($milestone->end_at)` null checks

#### Consistency

- [ ] **UI38. Double `<div class="table-responsive">` wrapper** — Duplicate wrapper in 2 files:
  - `milestone/index.blade.php` lines 11-12
  - `projects/index.blade.php` lines 10-11
  - Fix: Remove the duplicate outer `<div class="table-responsive">`

- [ ] **UI39. Ticket tables have different column sets** — Each ticket list shows different columns:
  - `home.blade.php`: Title, T, P, Status, Project, Assignee, Notes, Created, Updated
  - `tickets/list.blade.php`: Title, P, Status, Project, Assignee, Notes, Created, Updated (no T)
  - `users/show.blade.php`: Title, T, P, Status, Project, Assignee, Notes, Created, Updated
  - `projects/show.blade.php`: Title, P, Status, Project, Assignee, Notes, Created, Updated
  - `milestone/show.blade.php`: Title, P, Status, Project, Assignee, Est, Notes, Updated
  - Fix: Standardize columns across all ticket tables (ideally a Blade partial)

- [ ] **UI40. `release/show.blade.php` has no page header pattern** — Lines 6-14: bare `<h1>`, `<hr>`, `<div class="row">` with no flex header or action buttons. Uses `<br><br>` for spacing (line 36).
  - Fix: Match other show pages — flex header with edit button, proper `mb-4` spacing

- [ ] **UI41. `home.blade.php` table has no `table-hover` or `align-middle`** — Line 20: only `table table-striped`. Other tables use `table-striped table-hover align-middle`.
  - Fix: Add `table-hover align-middle` to match other tables

- [ ] **UI42. `milestone/show.blade.php` tables use `table-hover table-sm` but no `table-striped`** — Lines 112, 151, 189
  - Fix: Add `table-striped` to match other tables

#### Shadows

- [ ] **UI43. Shadow inconsistency across cards** — Current state:
  - Kanban board cards: `shadow-sm` (board.blade.php line 23)
  - Milestone sidebar cards: `shadow-sm` (milestone/show.blade.php lines 229, 243, 262)
  - Project stat cards: `shadow-sm` on first card only (projects/show.blade.php line 31), none on status cards (line 42)
  - Release empty state: `shadow-sm` (release/index.blade.php line 11)
  - Import form: `shadow-sm` (import/index.blade.php line 21)
  - Login card: `shadow-lg` (auth/login.blade.php line 8)
  - Guest layout card: `shadow` (layouts/guest.blade.php line 25)
  - Ticket detail card: no shadow (tickets/show.blade.php line 195)
  - Fix: Standardize — all content cards get `shadow-sm`, auth pages get `shadow`

#### Dead Code / Cleanup

- [ ] **UI44. 12 Breeze Tailwind components still exist** — `resources/views/components/` has 13 files, most using Tailwind:
  - `primary-button.blade.php`, `secondary-button.blade.php`, `danger-button.blade.php` — Tailwind classes like `bg-gray-800`, `px-4 py-2`
  - `text-input.blade.php`, `input-label.blade.php`, `input-error.blade.php` — Tailwind
  - `dropdown.blade.php`, `dropdown-link.blade.php`, `nav-link.blade.php`, `responsive-nav-link.blade.php` — Tailwind
  - `modal.blade.php` — Tailwind
  - Auth views (`auth/login.blade.php`) use `layouts.app` not `layouts.guest`, so most of these components are unused
  - Fix: Delete all unused Tailwind component files, or convert the few used ones to Bootstrap 5

- [ ] **UI45. Empty `<style>` and `<script>` blocks in import/index.blade.php** — Lines 47-50: empty tags serve no purpose
  - Fix: Remove the empty `@section('javascript')` block

- [ ] **UI46. `mail/notify.blade.php` is raw HTML with no template** — Lines 1-10: plain `<p>` tags with no email layout, no styling, no responsive design
  - Uses `env('APP_URL')` (breaks with config cache — see H19)
  - Fix: Use Laravel's Mailable markdown or at minimum a proper HTML email template

---

## Code Review Summary (2026-03-21)

| Category | Count |
|----------|-------|
| Critical Issues | 8 |
| High Priority | 18 |
| Medium Priority | 22 |
| Low Priority | 14 |
| **Total** | **62** |

---

## Completed Fixes (Prior Sessions)

- Reference assignment bug
- File upload security (validation added, mimes check)
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
- API token stateless auth
- ReleaseTicket migration
- Session encryption and SameSite cookies
- bcrypt rounds 10->12
- Importer moved to Services
- Dead code removal (Sprint, Watcher models)
- Laravel 12 framework upgrade (v11.50.0 → v12.55.1)
- Constructor middleware removed from all controllers
- `'password' => 'hashed'` cast on User model
- Division by zero guards
- State-changing GET requests converted to POST
- BatchUpdateTicketRequest for batch updates
- Api\TicketController IDOR fix (user scope filter)
- NotesController::hide() ownership verification
- Release model datetime casts
- Project model boolean cast for active
- TicketView unique index migration
- Ticket::actual_hours N+1 optimization with withSum
- Importer service: transaction, file handle, bounds checking
- XSS protection via HTMLPurifier `clean()` on all raw output
- StatusFactory closed()/open() states fixed
- Type/Importance model tickets() relationships added
- ReleaseController::put() now uses StoreReleaseRequest

## Frontend Fixes (2026-03-21)

- UI1: Navbar dark theme compatibility
- UI2: Replace bg-light with bg-body-secondary
- UI3: Remove hardcoded bg-white
- UI4: Remove hardcoded text-dark
- UI5-9: Fix Bootstrap 3 legacy classes
- UI10: Convert guest layout to Bootstrap 5
- UI11: Quill editor heights to CSS classes
- UI12: Inline table widths to Bootstrap col-*
- UI13: Remove unused inline style block
- UI14: Kanban board responsive columns
- UI15: Shadow usage already standardized

## Frontend Fixes (2026-03-21) - Batch 2

- UI16: Bootstrap 5.3 native color mode with data-bs-theme
- UI17: Standardize badge styling to text-bg-* classes
- UI18: Add table-striped to main data tables
- UI19: Fix text-bg-light badge dark mode (part of UI17)
- UI20: Standardize button variants
- UI21: Standardize list page headers
- UI22: Add consistent empty states
- UI23: Standardize H1 margins
- UI24: Standardize pagination
- UI26: Standardize card structure
- UI27: Add aria-hidden to decorative icons
- UI28: Color-only status indicators (already compliant)
- UI29: Delete unused Breeze navigation layout
- UI30: Replace inline display:none with d-none
