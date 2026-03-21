# Project Issues Tracker

## Code Review Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 6 |
| HIGH | 6 |
| MEDIUM | 10+ |
| LOW | 7+ |

---

## CRITICAL Issues - ALL FIXED ✅

- [x] Reference Assignment Bug
- [x] Release.owner() Wrong Relationship
- [x] Milestone.owner()/scrummaster() Wrong Relationship
- [x] Note.created_at in Fillable
- [x] ReleaseController Empty String Dates
- [x] Project.completion Logic Inverted
- [x] N+1 in UsersController::show()
- [x] UsersController Mass Assignment
- [x] ReleaseTicket Migration
- [x] API Token Session Hijacking
- [x] TrustProxies Configuration

---

## HIGH Priority Issues

- [x] **Missing Form Request Classes** ✅
  - Fixed: StoreTicketRequest, UpdateTicketRequest, EstimateTicketRequest

- [x] **No Authorization Policies** ✅
  - Fixed: TicketPolicy, MilestonePolicy

- [x] **Missing Pagination** ✅
  - Fixed: board(), index(), API endpoints now paginated

- [x] **Missing $casts on Models** ✅
  - Fixed: Added to Ticket, User, Note models

- [x] **Magic Numbers** ✅
  - Fixed: Status::closedStatusIds() method

---

## MEDIUM Priority Issues

### Security

- [x] **API Token Uses Plain SHA256** - Token hashing uses SHA256
- [x] **Session Not Encrypted** ✅
  - Fixed: encrypt=true
- [x] **Missing SameSite Cookie Attribute** ✅
  - Fixed: same_site='lax'

### Performance

- [ ] **Missing Database Indexes** - Check migrations

### Code Quality

- [ ] **Inconsistent Relationship Naming** - user vs assignee
- [ ] **Sprint Model Empty** - May be dead code
- [ ] **Watcher Model May Be Duplicate** - TicketUserWatcher vs Watcher

---

## LOW Priority Issues

- [x] **Importer.php in Models Directory** ✅
  - Fixed: Moved to Services
- [x] **Wrong Import Path in Importer.php** ✅
  - Fixed: Proper namespace imports
- [ ] **Sprint Model Wrong Table Name**
- [ ] **Inconsistent Indentation** - Mix of 2 and 4 spaces
- [ ] **Comments in Production Code**

---

## Completed Fixes

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

---

## Code Review (2026-03-21) — New Findings

### CRITICAL — New Issues Found

- [ ] **Stored XSS — Unescaped HTML rendering**
  - `resources/views/tickets/show.blade.php:61,110,131`
  - `{!! html_entity_decode($ticket->description) !!}` and `{!! html_entity_decode($note->body) !!}` render raw HTML from user input (Quill.js)
  - Any authenticated user can inject `<script>` tags into ticket descriptions or notes
  - Fix: Install `mews/purifier` and sanitize HTML on input

- [ ] **Mass Assignment — `update()` bypasses UpdateTicketRequest**
  - `app/Http/Controllers/TicketsController.php:201`
  - Method accepts `Request` instead of `UpdateTicketRequest`, passes `$request->toArray()` directly to `$ticket->update()`
  - Attacker can modify any fillable field (user_id, user_id2, etc.)
  - Fix: Change signature to `update(UpdateTicketRequest $request, $id)`, use `$request->validated()`

- [ ] **Mass Assignment — `batch()` has zero validation**
  - `app/Http/Controllers/TicketsController.php:260-296`
  - `$request->toArray()` passed directly to `$update->update($post)` — no validation, no authorization
  - Any authenticated user can batch-modify any ticket's any fillable field
  - Fix: Create `BatchUpdateTicketRequest`, add per-ticket authorization

- [ ] **Mass Assignment — `MilestoneController::store()` and `ProjectsController::store()`**
  - `MilestoneController.php:132`, `ProjectsController.php:82`
  - `$request->toArray()` passed to `create()` — includes _token and any injected fields
  - Fix: Use Form Requests with `$request->validated()`

- [ ] **Missing Authorization on destructive actions**
  - `NotesController::hide()` — any user can hide anyone's notes
  - `TicketsController::batch()` — no `$this->authorize()` call
  - `MilestoneController::store/update` — no authorization
  - `ProjectsController::store()` — no authorization
  - `ReleaseController::put/store()` — no authorization
  - `ImportController::create()` — any user can bulk import
  - `Api\TicketController::note()` — any API user can claim/modify ANY ticket
  - Fix: Add `$this->authorize()` calls to every state-changing action

- [ ] **Broken import — wrong namespace**
  - `app/Models/Importer.php:5`
  - `use App\Type;` should be `use App\Models\Type;`
  - CSV import throws fatal error on every call
  - Fix: Change to `use App\Models\Type;`

### HIGH — New Issues Found

- [ ] **State-changing GET requests (CSRF bypass)**
  - `GET /tickets/claim/{id}`, `GET /tickets/watch/{id}`, `GET /notes/hide/{id}`, `GET /milestone/watch/{id}`, `GET /users/watch/{id}`, `GET /tickets/api/{id}`
  - Attacker can embed `<img src="/tickets/claim/42">` in a note (rendered as raw HTML via XSS issue) to trigger actions
  - Fix: Change all to POST/PUT/DELETE with CSRF protection

- [ ] **N+1 Query Problems (250+ queries per page)**
  - `TicketsController::index()` — never eager loads; blade accesses importance, type, status, project, assignee per row
  - `ReleaseController::show():74-82` — `$ticket->ticket->project`, `$ticket->ticket->type` in loop
  - `tickets/show.blade.php:82-87` — 4 separate `$ticket->notes()->where()->count()` queries
  - `tickets/show.blade.php:288` — raw DB query inside blade template
  - `tickets/list.blade.php:124` — `$tick->notes()->count()` per row
  - Fix: Add `->with([...])` eager loading, use `withCount`, move queries out of blade

- [ ] **No CSRF on AJAX calls**
  - `resources/views/tickets/show.blade.php:350,380`
  - `fetch('/notes/hide/' + noteid)` and `fetch('/users/watch/' + ticketId)` — GET requests, no CSRF
  - Fix: Convert to POST with CSRF token in headers

- [ ] **Email uniqueness not enforced**
  - `app/Http/Controllers/UsersController.php:63`
  - Missing `unique:users,email,$user->id` — user can take another user's email
  - Fix: Add unique rule excluding current user

- [ ] **ReleaseController — no input validation**
  - `app/Http/Controllers/ReleaseController.php:40-103`
  - `put()` and `store()` have no validation — no length limits on title/body
  - Fix: Add Form Request validation

- [ ] **DOM XSS via innerHTML**
  - `resources/views/tickets/show.blade.php:383`
  - `alertMessage.innerHTML = data;` — server response inserted as HTML
  - Fix: Use `alertMessage.textContent = data;`

### MEDIUM — New Issues Found

- [ ] **`notate()` builds HTML strings unsafely**
  - `TicketsController.php:526-537`
  - `'<li>'.$change.'</li>'` — lookup names from DB not escaped before storing as HTML
  - Fix: Use `'<li>'.e($change).'</li>'`

- [ ] **Hardcoded status ID `5` for "closed" — inconsistent with closedStatusIds()**
  - `TicketsController.php:338`, `Api\TicketController.php:161`
  - Checks `== 5` but `closedStatusIds()` returns `[5, 8, 9]` — status 8/9 won't set `closed_at`
  - Fix: Use `Status::isClosed($request->status_id)`

- [ ] **`MilestoneController::edit()` parameter mismatch**
  - `MilestoneController.php:120-122` — has `$id` param but uses `$request->id`
  - Fix: Use `$id` consistently

- [ ] **`MilestoneController::report()` burndown bug**
  - `MilestoneController.php:266` — calls `->get()` on a Collection (not Builder), ignores whereIn filter
  - Burndown chart data will be incorrect
  - Fix: Remove `->get()`

- [ ] **`TicketsController::index()` search filter always applies for `q`**
  - `TicketsController.php:82-84` — runs regardless of whether `q` has a value
  - Fix: Add `&& !empty($request->$filter)` check

- [ ] **Missing `$fillable` on `ReleaseTicket` model**
  - `app/Models/ReleaseTicket.php` — no `$fillable` or `$guarded`
  - Fix: Add `protected $fillable = ['release_id', 'ticket_id'];`

- [ ] **Cache invalidation missing for lookups**
  - `TicketsController.php:359` — cached 60 min, no invalidation when items created/updated
  - Fix: Add `Cache::forget('ticket_lookups')` in store/update methods

- [ ] **`estimate()` — two identical DB queries**
  - `TicketsController.php:415-416` — `Ticket::find()` called twice for same ID
  - Fix: `$old = $ticket->toArray()` then modify `$ticket`

- [ ] **Duplicate watch functionality**
  - `UsersController::watch()` and `TicketsController::toggleWatcher()` do the same thing
  - Fix: Remove one, redirect the other

- [ ] **Import validation missing**
  - `ImportController.php:28` — no `$request->validate()`, crashes if no file uploaded
  - `milestone_id` not validated, `hasHeader` cast to string not boolean
  - Fix: Add validation rules

### LOW — New Issues Found

- [ ] **Legacy service providers still exist alongside bootstrap/app.php**
  - `AuthServiceProvider`, `EventServiceProvider`, `BroadcastServiceProvider`, `RouteServiceProvider`
  - Laravel 11 consolidated into `bootstrap/app.php` — dual registration
  - Fix: Move policy registration to `AppServiceProvider::boot()`, delete legacy providers

- [ ] **Constructor middleware (deprecated pattern)**
  - All controllers use `$this->middleware('auth')` — redundant with route group middleware
  - Fix: Remove from all controllers

- [ ] **String-based relationship definitions**
  - All models use `'App\Models\Type'` strings instead of `Type::class`
  - Fix: Replace with `::class` constants

- [ ] **Typo: "Aisa" should be "Asia"**
  - `app/Http/Controllers/UsersController.php:119`

- [ ] **Dead code to remove**
  - `HomeController` — unused, `/home` route points to `TicketsController::home()`
  - `Watcher` model — empty, unused
  - `TicketsController::fetch()` — no route defined
  - `App\Http\Requests` import in `HomeController` — unused

- [ ] **Importer — dead code after throw**
  - `Importer.php:58` — `return $model ? $model->id : null;` ternary pointless after throw
  - Fix: Simplify to `return $model->id;`

- [ ] **Missing `password` hashed cast on User model**
  - Laravel 11 supports `'password' => 'hashed'` for automatic hashing

---

## Laravel 12 Upgrade Path

- [ ] Update `composer.json`: `laravel/framework` → `^12.0`, `sanctum` → v5.x, `breeze` → v3.x, `collision` → v9.x
- [ ] Remove `illuminate/filesystem` from composer.json (included with framework)
- [ ] Remove legacy providers (`Auth`, `Event`, `Broadcast`, `Route` ServiceProviders)
- [ ] Move policy registration to `AppServiceProvider::boot()` or rely on auto-discovery
- [ ] Remove `Kernel.php` and `Handler.php` (Laravel 10 patterns)
- [ ] Convert `protected $casts` property to `protected function casts(): array` method
- [ ] Remove constructor middleware from all controllers
- [ ] Remove `$this->registerPolicies()` — auto-discovered in Laravel 12
- [ ] Review all deprecated methods
