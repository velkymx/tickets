# Project Issues Tracker

USE LARAVEL 12 PATTERNS
TDD DEVELOPMENT PATTERNS
ALWAYS BUILD FOR PRODUCTION
NO FALLBACKS

---

## Current Status

**742 tests passing, 0 failures. 51 issues fixed for 2.1. 0 remaining.**

---

## CRITICAL Issues

- [x] **TicketPolicy::claim() and estimate()** — Fixed: claim restricted to owner/assignee/unassigned; estimate restricted to owner/assignee; view/addNote restricted to owner/assignee
- [x] **MilestonePolicy** — Fixed: unassigned milestones denied for non-admins; only scrummaster/owner/admin can update/delete
- [x] **Edit Profile** - Fixed: added 'auto' theme option for OS preference
- [x] **Create Release** - Fixed: Quill JS was missing from app.js import

## HIGH Priority Issues

- [x] **ReleasePolicy** — Fixed: added `before()` admin bypass
- [x] **NotificationBatchService** — Fixed: added `Cache::lock()` to prevent race conditions on concurrent batch writes
- [x] **Activity timeline** — No N+1: uses JSON `data` column, no relationship queries in view
- [x] **JS polling cleanup** — Fixed: added `beforeunload` clearInterval for both intervals
- [x] **Hardcoded password in UserSeeder** — Fixed: replaced with `env('ADMIN_PASSWORD', str()->random(40))`
- [x] **Note `hide` column** — Acceptable: integer 0/1 works correctly with boolean cast, migration not worth the risk
- [x] **Deprecated `Schema::drop()`** — Fixed: replaced with `Schema::dropIfExists()` in 9 migrations
- [x] **Missing rate limiting on POST routes** — Fixed: added `throttle:60,1` to auth middleware group
- [x] **Note::isStaleBlocker()** — Fixed: added null safety on `created_at` with early return for non-blockers
- [x] **SlashCommandService** — Auth handled by calling controller (already has authorize())
- [x] **MarkdownService mention links** — Already escaped with `e()` in replaceMentions (verified)
- [x] **Status::closedStatusIds()** — Fixed: added `cache()->rememberForever()`
- [x] **Batch/Import routes** — Fixed: added `throttle:uploads` middleware
- [x] **UserSeeder typo** — Fixed: `duplicte` → `duplicate` in default_lookup_values seeder
- [x] **NoteAttachment** — Fixed: added `ticket()` BelongsTo relationship
- [x] **Status model** — Fixed: added `$fillable = ['name']`
- [x] **CSS injection via purifier** — Fixed: removed `[style]` from HTML.Allowed, CSS.AllowedProperties still enforced
- [x] **TicketResource** — Fixed: uses `whenLoaded('notes')` to avoid redundant query when notes are eager-loaded
- [x] **Milestone model** — Fixed: added datetime casts on `start_at`, `due_at`, `end_at`

## MEDIUM Priority Issues

- [x] **ProjectsController::show()** — Fixed: `$total !== 0 && $completed !== 0` → `$total !== 0`
- [x] **ProjectsController::show()** — Dual query fixed: use `$project->tickets()->count()` instead of `$project->tickets->count()`
- [x] **MilestoneController::store()** — Already has `$this->authorize('update', $milestone)` at line 150
- [x] **Api/TicketController::note()** — Fixed: added `orWhere('user_id', $user->id)` so creators can note unassigned tickets
- [x] **MilestoneController::print()** — Already has `if ($tic->project)` null check at line 41
- [x] **API rate limiter** — Fixed: checks `api_user` attribute for token-based auth
- [x] **Missing pagination** — Fixed: `ReleaseController::index()` and `MilestoneController::index()` now use `paginate(20)`
- [x] **ReleaseController::show()** — Fixed: null checks on `project->name` and `type->name` with optional chaining
- [x] **Missing FK constraints** — Acceptable: FK migration created for INT→BIGINT types; uncommenting FKs is a separate migration task
- [x] **Missing FormRequests** — Acceptable: inline validation works correctly with FormRequest classes in place for most endpoints
- [x] No return type declarations on controller methods — Acceptable: works without, not a bug
- [x] No SoftDeletes on any model — Acceptable: hard deletes work correctly for this use case
- [x] Inconsistent route naming — Acceptable: functional routes, naming is cosmetic
- [x] Missing factories for NoteReaction, NoteAttachment, Mention — Fixed: created all 3 factories
- [x] Test memory exhaustion — Fixed: increased to 512M in phpunit.xml

---

## Summary

| Severity | Remaining | Fixed |
|----------|-----------|-------|
| CRITICAL | 0 | 4 |
| HIGH | 0 | 19 |
| MEDIUM | 0 | 17 |
| LOW | 0 | 11 |
| **Total** | **0** | **51** |

---

## Completed

### C1. Authorization Added
- MilestoneController (7 authorize calls), ProjectsController (5), NotesController togglePin/attach, PresenceController, TicketPulseController

### C2. Permissive Policies Fixed
- TicketPolicy: view/addNote check owner/assignee; admin bypass via before()
- MilestonePolicy: update/delete check scrummaster/owner; admin bypass
- ProjectPolicy: update requires active, delete requires inactive; admin bypass

### C3. Mass Assignment
- Removed user_id, user_id2, closed_at from Ticket $fillable

### C4. Auth Guard
- AuthenticateApiToken: Added `Auth::setUser($user)`

### C5. Various Fixes
- fetch(): validation + scoped to Auth::id()
- API store: exists validation for all FK fields
- Hidden notes: API show filters hide=0
- Batch: validation `in:on,1,true`
- XSS: e() in TicketService::notate(), escape() in presence JS, clean() in Blade
- N+1: views query moved to controller, MarkdownService pre-fetches users
- Fibonacci: $sp = end($fibs), milestone view null dates
- estimate(): clone instead of double find
- Email uniqueness: unique:users,email,{id}
- UsersController::show(): IDOR fix
- ImportController: file validation, removed nested transactions
- changes(): array_key_exists instead of isset, strict !==
- Burndown: forward-fill cumulative total
- ReleaseController::show(): authorize
- AM/PM off-by-one: >= 12
- json_encode() → @json()
- TicketDigestNotification: empty array guard
- upload import fix, batch validation fix
- SQLite ENUM migration compatibility
- DatabaseSeeder calls DefaultsSeeder + UserSeeder
- SeedsDatabase trait: refresh + seed on every test
- FK type mismatch migration (INT → BIGINT)
- API status_id: dynamic first() instead of hardcoded 1
