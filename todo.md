# Project Issues Tracker

USE LARAVEL 12 PATTERNS
TDD DEVELOPMENT PATTERNS
ALWAYS BUILD FOR PRODUCTION
NO FALLBACKS

---

## Current Status

**742 tests passing, 0 failures. Code review round 3 complete.**

---

## CRITICAL Issues

### C1. All Policies Return `true` — No Real Authorization (RECURSIVE)

- [x] **TicketPolicy** — `view`, `update`, `claim`, `estimate`, `addNote` all return `true`
- [ ] **MilestonePolicy** — `update`, `delete` return `true`
- [ ] **ProjectPolicy** — `update`, `delete` return `true`
  - These have been fixed and reverted externally multiple times
  - Fix: Add ownership checks, add before() admin bypass, stop reverting

### C2. `admin` Field Mass-Assignable

- [x] **User.php** (Line 17) — `'admin'` in `$fillable`. Any update could grant admin privileges
  - Fix: Remove from `$fillable`, use dedicated `promoteToAdmin()` method

### C3. `where('end_at', null)` — SQL Bug (Milestones Always Empty)

- [x] **TicketService.php** (Line 282) — `->where('end_at', null)` generates `WHERE end_at = NULL` which never matches in SQL
  - Fix: Change to `->whereNull('end_at')`

### C4. `default_lookup_values.php` — Dead/Duplicate Seeder

- [x] **default_lookup_values.php** — Duplicates `DefaultsSeeder.php`, has no idempotency, PSR-4 non-compliant class name
  - Fix: Delete file entirely

### C5. Upload Folder — Path Traversal Risk

- [x] **UploadTicketRequest.php** (Line 18) — `folder` accepts any string, no regex validation
  - Fix: `'folder' => 'required|string|max:50|regex:/^[a-zA-Z0-9_\-]+$/'`

---

## HIGH Priority Issues

### H1. API Endpoints Use Data Filtering Not Authorization

- [ ] **Api/TicketController.php** — Uses `where('user_id2', $user->id)` to filter data, no `$this->authorize()` calls
  - Fix: Add `Gate::authorize()` or policy checks

### H2. RouteServiceProvider — Dead Code in Laravel 12

- [ ] **RouteServiceProvider.php** — Never loaded by Laravel 12's bootstrap/app.php. Contains duplicate rate limiter
  - Fix: Delete file

### H3. Duplicate Rate Limiter Registration

- [ ] **AppServiceProvider.php** and **RouteServiceProvider.php** — Both define `RateLimiter::for('api', ...)`
  - Fix: Remove from RouteServiceProvider (see H2)

### H4. `admin` in User Seeder — `env()` Breaks With Config Cache

- [x] **UserSeeder.php** (Line 33) — `env('ADMIN_PASSWORD')` returns null when config is cached
  - Fix: Use `config('admin.password')` with proper config entry

### H5. Flare Config Sends Query Bindings Externally

- [x] **config/flare.php** (Line 53) — `'report_query_bindings' => true` sends sensitive data to external servers
  - Fix: Set to `false` by default

### H6. Ignition Share Button Enabled by Default

- [x] **config/ignition.php** (Line 83) — Default `true` allows sharing stack traces publicly
  - Fix: Set default to `false`

### H7. TicketResource — N+1 Queries on Every Serialization

- [x] **TicketResource.php** — `$this->status->name`, `$this->assignee->name`, `$this->notes()->count()` trigger lazy loads
  - Fix: Always use `withCount` and eager-load in controller

### H8. StatusFactory::open() Name Mismatch With Seeder

- [x] **StatusFactory.php** (Line 29) — `open()` creates `'Open'` but seeder defines `'new'`
  - Fix: Change to `'new'` to match seeder

### H9. LIKE Search in SlashCommandService Matches Wrong Users

- [x] **SlashCommandService.php** (Line 236) — `%Jo%` matches "John", "Jojo", "Jones"
  - Fix: Remove LIKE fallback for User lookups

### H10. TicketDigestNotification Not Queued

- [ ] **TicketDigestNotification.php** — Does not implement `ShouldQueue`. Email failures block the job
  - Fix: Add `implements ShouldQueue`

---

## MEDIUM Priority Issues

### M1. `notate()` Truncates Decimal Hours

- [x] **TicketService.php** (Line 108) — `(int)` cast loses `0.5` hours from slash commands
  - Fix: Change to `(float)`

### M2. `toggleReaction` Race Condition

- [ ] **NotesController.php** (Line 277) — Check-then-act without transaction
  - Fix: Wrap in `DB::transaction()` or add unique constraint
  - Note: Transaction attempt caused test failures, reverting

### M3. `resolve` Excludes Ticket Creator

- [ ] **NotesController.php** (Line 249) — Only note author and assignee can resolve; creator excluded
  - Fix: Add `$note->ticket->user_id` check

### M4. `activeStatusIds()` Not Cached

- [ ] **Status.php** (Line 37) — Queries DB on every call while `closedStatusIds()` is cached
  - Fix: Cache similarly

### M5. `changes()` Strict Comparison Ignores Type Coercion

- [ ] **TicketService.php** (Line 49) — `!==` treats `int(1)` and `string("1")` as different
  - Fix: Normalize types before comparison on ID fields

### M6. Missing Max Validation on Numeric Fields

- [ ] **StoreTicketRequest.php** — `estimate`, `storypoints` have no `max` limit
  - Fix: Add `max:99999`

### M7. `User::tickets()` and `owner()` Naming Confusing

- [ ] **User.php** (Lines 59-67) — `tickets()` returns assigned, `owner()` returns created
  - Fix: Rename to `assignedTickets()` and `createdTickets()`

### M8. Loose `==` Comparison for `'new'` String

- [ ] **MilestoneController.php** (Line 142), **ProjectsController.php** (Line 94) — `$request->id == 'new'`
  - Fix: Use `===` strict comparison

### M9. `TicketResource::notetype_summary` Redundant Query

- [ ] **TicketResource.php** (Lines 32-41) — GROUP BY runs per ticket even when notes loaded
  - Fix: Use `whenLoaded()` with collection grouping

### M10. ActivityController — No Pagination

- [ ] **ActivityController.php** (Line 31) — `->get()` loads ALL notifications
  - Fix: Use `->paginate(20)`

### M11. `ActivityController::readAll` N+1

- [ ] **ActivityController.php** (Line 49) — Loads all unread into memory
  - Fix: `unreadNotifications()->update(['read_at' => now()])`

### M12. `StatusFactory::open()` Casing Mismatch

- [ ] **StatusFactory.php** — Creates `'Open'` (capital), seeder uses `'new'` (lowercase)
  - Fix: Match seeder casing

### M13. `NoteTicketRequest::notetype` Allowed Values Wrong

- [ ] **NoteTicketRequest.php** (Line 21) — Allows `info,query` but actual types are `blocker,decision,action,changelog`
  - Fix: Update to match actual types

### M14. Importer Double-Reads File

- [ ] **ImportController.php** + **Importer.php** — File read twice (once for count, once for import)
  - Fix: Pass row count to Importer or count internally

### M15. `getLookups()` Only Checks Status Count for Cache

- [ ] **TicketService.php** (Lines 263-276) — Invalidates on status count change but ignores types/projects/users
  - Fix: Use cache tags or model observers

---

## LOW Priority Issues

- [ ] **L1.** Sanctum installed but unused — custom `api.token` middleware instead of `auth:sanctum`
- [ ] **L2.** `tickets.api` route uses POST for what appears to be data fetch
- [ ] **L3.** `Importer::call()` does not guard against `fopen` failure
- [ ] **L4.** `Note::groupedReactions()` depends on global `auth()` state
- [ ] **L5.** `TicketService::changes()` uses `substr` magic numbers for field labels
- [ ] **L6.** `MarkdownService::decorateChecklistItems()` brittle string replacement
- [ ] **L7.** Missing factories match seeder values (TypeFactory, ImportanceFactory)
- [ ] **L8.** `getSignalType()` priority order undocumented
- [ ] **L9.** `Note::isStaleBlocker()` uses implicit `now()` — not testable

---

## Summary

| Severity | Remaining |
|----------|-----------|
| CRITICAL | 5 |
| HIGH | 10 |
| MEDIUM | 15 |
| LOW | 9 |
| **Total** | **39** |

---

## Completed — Prior Sessions

### Completed CRITICAL Fixes (1-5)
- Auth guard in middleware, fetch() validation, IDOR authorization, API store validation, hidden notes API, batch validation, XSS in notate() and presence JS, N+1 views query

### Completed HIGH Fixes (6-19)
- Fibonacci rounding, estimate double fetch, email uniqueness, ImportController validation, changes() null-to-value, burndown chart, ReleaseController auth, TicketDigestNotification empty array, Status::closedStatusIds() cache, MarkdownService N+1, note togglePin/attach auth, PresenceController auth, TicketPulseController auth, TicketPolicy auth (reverted), MilestonePolicy auth (reverted), admin bypass, upload tests, fetch tests, order by id in lookups

### Completed MEDIUM Fixes (20-33)
- AM/PM off-by-one, loose comparison, ProjectsController completion logic, ProjectsController dual query, MilestoneController store auth, API note unassigned, perpage cap, strict comparison, eager loads, lookup crash, int→float, NoteAttachment ticket(), Status fillable, Milestone date casts, TicketUserWatcher muted, default_lookup_values typo, View→view, Schema::drop, throttle middleware, expand_notetype down(), dangerouslyPasteHTML verified

### Completed LOW Fixes (34-41)
- Factory fixes, memory limit, status_id hardcoded, @json directive, AM/PM
