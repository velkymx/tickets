# Ticket Work Surface — Real-Time Decision Log

**Target Release: v2.1.0** | Branch: `2.0.0` → merge to `master`

## Philosophy

This is not a comments system. It's a **real-time decision log and work surface** that sits between Slack (communication) and Jira (structure) without becoming either.

The primary interface is the **Ticket Pulse** — a deterministic, always-visible panel that surfaces meaning from ticket activity. The timeline is secondary. A developer should go from landing on a ticket to understanding the full state in **under 10 seconds**, without scrolling.

Design principles:
- **Comprehension over interaction** — surfacing meaning matters more than better inputs
- **Command-driven, not UI-driven** — power users never touch a dropdown
- **Derived, not entered** — signal is extracted from behavior, not form fields
- **Scannable in 3 seconds** — visual noise is a bug
- **Keyboard-first** — mouse is optional
- **Mobile-real** — devs reply from their phone

## Current State

- **Note model:** `id`, `body`, `user_id`, `ticket_id`, `hours`, `notetype` (message|changelog|misc), `hide`, `timestamps`
- **View:** `tickets/show.blade.php` — two separate tabs (Messages, Changelog), flat card list
- **Form:** Quill rich text editor + status dropdown + hours input
- **TicketService::notate()** — creates separate `message` and `changelog` Note records
- **WatcherNotification** — email-only via `ShouldQueue`
- **Views tracking** — `ticket_views` table tracks who viewed when
- **No:** avatars, reactions, threading, @mentions, attachments, editing, slash commands, presence

---

## Implementation Plan

### Phase 1: Database Migrations

- [x] **1.1 — Add `parent_id` to notes table** (threading)
  - `$table->unsignedInteger('parent_id')->nullable()->after('ticket_id')`
  - FK to `notes.id`, `onDelete('cascade')`
  - Index on `parent_id`

- [x] **1.2 — Expand `notetype` enum on notes table**
  - Current: `message`, `changelog`, `misc`
  - New values: `decision`, `blocker`, `update`, `action`
  - Migration: modify enum to include all seven types
  - `message` remains the default for normal comments

- [x] **1.3 — Add `edited_at` to notes table**
  - `$table->timestamp('edited_at')->nullable()->after('hide')`

- [x] **1.4 — Add `pinned`, `resolved`, and `supersedes_id` to notes table**
  - `$table->boolean('pinned')->default(false)->after('edited_at')`
  - `$table->boolean('resolved')->default(false)->after('pinned')`
  - `$table->unsignedInteger('resolved_by')->nullable()->after('resolved')`
  - `$table->unsignedInteger('supersedes_id')->nullable()->after('resolved_by')` — FK to `notes.id`, for decision immutability chain
  - `$table->text('resolution_message')->nullable()->after('supersedes_id')` — required when resolving threads

- [x] **1.5 — Add `body_markdown` to notes table**
  - `$table->longText('body_markdown')->nullable()->after('body')`
  - Stores raw Markdown source; `body` stores rendered HTML
  - Allows re-editing from Markdown, not reverse-engineering HTML

- [x] **1.6 — Create `note_reactions` table**
  - Fields: `id`, `note_id` (FK), `user_id` (FK), `emoji` (string)
  - Unique on `['note_id', 'user_id', 'emoji']`
  - **v2.1:** only `thumbsup` and `eyes` — no picker, just two inline buttons
  - Full set deferred to v2.2: `thumbsdown`, `check`, `tada`, `heart`, `thinking`

- [x] **1.7 — Create `note_attachments` table**
  - Fields: `id`, `note_id` (FK, **not nullable** — v2.1 attaches on submit only), `user_id` (FK), `ticket_id` (FK), `filename`, `path`, `mime_type`, `size`, `timestamps`
  - Index on `note_id`, index on `ticket_id`
  - **v2.1:** no pre-attach, no paste detection upload — simple file upload on submit

- [x] **1.8 — Create `mentions` table**
  - Fields: `id`, `note_id` (FK), `user_id` (FK — mentioned user), `timestamps`
  - Unique on `['note_id', 'user_id']`
  - Index on `user_id` for Activity Center queries

- [x] **1.9 — Add `avatar` to users table**
  - `$table->string('avatar')->nullable()->after('bio')`
  - Gravatar fallback only for v2.1 — no upload UI

- [x] **1.10 — Create Laravel notifications table**
  - `php artisan notifications:table` (if not already present)
  - Enables database notification channel for Activity Center

### Phase 2: Models

- [x] **2.1 — Update Note model**
  - Add to `$fillable`: `parent_id`, `edited_at`, `pinned`, `resolved`, `resolved_by`, `supersedes_id`, `resolution_message`, `body_markdown`
  - Add to `$casts`: `edited_at` => `datetime`, `pinned` => `boolean`, `resolved` => `boolean`
  - Relationships: `parent()`, `replies()`, `reactions()`, `attachments()`, `mentions()`, `resolvedByUser()`, `supersedes()`, `supersededBy()`
  - Helpers: `isEdited()`, `groupedReactions()`, `hasReacted(User, emoji)`, `isResolved()`, `isSuperseded()`, `isStaleBlocker()`
  - Scope: `scopePinned()`, `scopeTopLevel()` (whereNull parent_id), `scopeActiveActions()`, `scopeActiveBlockers()`

- [x] **2.2 — Create NoteReaction model**
  - `$fillable`: `note_id`, `user_id`, `emoji`
  - Constant: `ALLOWED_EMOJIS = ['thumbsup', 'eyes']` (v2.1 — expand in v2.2)
  - Relationships: `note()`, `user()`

- [x] **2.3 — Create NoteAttachment model**
  - `$fillable`: `note_id`, `user_id`, `ticket_id`, `filename`, `path`, `mime_type`, `size`
  - Relationships: `note()`, `user()`
  - Accessors: `url()`, `isImage()`

- [x] **2.4 — Create Mention model**
  - `$fillable`: `note_id`, `user_id`
  - Relationships: `note()`, `user()`

- [x] **2.5 — Update User model**
  - Add `avatar` to `$fillable`
  - Add `avatarUrl($size = 46)` — Gravatar from email hash, `?d=mp`
  - Relationships: `mentions()`, `reactions()`

### Phase 3: Ticket Pulse — The Primary Interface

The Ticket Pulse is not a widget. It is the **primary interface** for understanding a ticket. The timeline is the detail view. A developer reads the Pulse and knows: what's happening, what's decided, what's stuck, who owns it, and what happens next — in under 10 seconds.

- [x] **3.1 — Create TicketPulseService**
  - Dedicated service that computes the Pulse from ticket data
  - **Must be cached** — updated incrementally on note creation, status change, assignment change
  - Cache key: `ticket_pulse:{ticket_id}`, invalidated on any ticket or note mutation
  - Returns a `TicketPulse` value object with all fields below
  - Do NOT compute this ad-hoc in the controller

- [x] **3.2 — Pulse data fields**
  - **Status + Blocker relationship:**
    - If an active (non-resolved) `blocker` note exists → status displays as "BLOCKED" regardless of actual status
    - Shows blocker reason and who/what is blocking
    - `Blocked by: Waiting on API team`
    - If not blocked → shows normal status: `Status: Testing`
    - **Hard rule:** a ticket with an active blocker must not allow status transitions. `/status` commands are rejected, UI status dropdown is disabled. Only resolving the blocker unlocks status changes. Without this enforcement, Pulse and status diverge and trust breaks.
  - **Ownership clarity:**
    - If current user is assignee → "You own this" (visually prominent)
    - If unassigned → "Unassigned" (visually loud, attention color)
    - If assigned to someone else → "Owner: Sarah"
    - If blocked → "Waiting on: @mike" (extracted from blocker note @mentions)
  - **Next Action (strict sourcing — do NOT fall back to threads):**
    - Priority: 1) latest non-resolved `/action` note → 2) oldest unresolved `/action` note → 3) explicitly assigned checklist item → 4) **nothing**
    - If no action exists → "No next action defined" (muted)
    - Shows: action text + assigned person (from @mention)
    - `Next Action: QA verification — @john`
    - Unresolved threads are NOT actions. They are discussions. Including them makes Pulse noisy and kills trust.
  - **Latest Decision:**
    - Most recent non-hidden, non-superseded `decision` note
    - Shows: decision text (truncated), author, relative time
    - `"Using Redis locks instead of DB locks" — John, 2h ago`
    - If superseded previous decision, shows "Supersedes: [previous decision text]" link
    - Interactive: [View] jumps to note in timeline, [Update] opens `/decision` prefilled composer (creates new superseding note, not an edit)
  - **Open Threads:**
    - List unresolved threads by first line of parent note (as name), with reply count
    - Shows actual thread subjects, not just counts
    - `• Race condition discussion (3 replies)`
    - `• Retry logic edge case (1 reply)`
    - Each clickable → jumps to thread in timeline
    - Interactive: [Resolve] button on each
  - **Latest Blocker** (if active):
    - Full blocker text, author, time
    - Interactive: [Resolve] clears blocker, restores actual status
    - Interactive: [Edit] opens editing on the blocker note
  - **Execution State (derived, not entered):**
    - **ON TRACK** → has next action AND recent activity < 48h (`text-success`)
    - **AT RISK** → no next action OR stale activity > 48h (`text-warning`)
    - **BLOCKED** → existing blocker rule overrides all (`text-danger fw-bold`)
    - **IDLE** → no activity AND no action AND not closed (`text-muted`)
    - Displayed prominently at top of Pulse, before status. This is what managers and seniors scan first.
    - Computed deterministically from existing Pulse fields — no new data entry
  - **Staleness indicator:**
    - Last activity timestamp (most recent note of any type)
    - If no activity in 48+ hours → "No updates in 2 days" warning (subtle but visible)
    - Last decision timestamp shown alongside decision text

- [x] **3.3 — Create `partials/ticket-pulse.blade.php`**
  - Layout (always visible, sticky on scroll):
    ```
    ┌─ Ticket Pulse ───────────────────────────────────┐
    │                                                   │
    │  ● BLOCKED                                        │
    │                                                   │
    │  Status: In Progress (blocked)                    │
    │  Reason: Waiting on API team        [Resolve]     │
    │                                                   │
    │  Owner: You own this                              │
    │                                                   │
    │  Next Action:                                     │
    │  QA verification — @john                          │
    │                                                   │
    │  Latest Decision:                                 │
    │  "Using Redis locks instead of DB locks"          │
    │  — John, 2h ago                    [View] [Update]│
    │                                                   │
    │  Open Threads:                                    │
    │  • Race condition discussion (3)       [Resolve]  │
    │  • Retry logic edge case (1)           [Resolve]  │
    │                                                   │
    │  Last Update: 2h ago                              │
    │                                                   │
    └───────────────────────────────────────────────────┘
    ```
  - **Sticky behavior:** panel stays visible as user scrolls the timeline
  - **Responsive:** on mobile, collapses to summary bar with expand toggle
  - **Visual treatment:**
    - Blocked status: `text-danger fw-bold`
    - "You own this": `text-primary fw-bold`
    - Unassigned: `text-warning fw-bold`
    - Decisions: slight background tint (`bg-success-subtle`), decision icon
    - Staleness warning: `text-warning` with clock icon
    - Open threads: each is a clickable link to the thread anchor

- [x] **3.4 — Pulse cache invalidation**
  - Invalidate on: note created, note edited, note hidden, note resolved, status change, assignee change
  - Use model observers or events: `NoteObserver`, `TicketObserver`
  - Rebuild is cheap (single ticket query with eager loads) — cache is for avoiding per-request recomputation

- [x] **3.5 — Wire into TicketsController@show**
  - Call `TicketPulseService::build($ticket, $currentUser)`
  - Pass `$pulse` to view
  - Pulse panel rendered above timeline

### Phase 4: Markdown & Slash Command Engine

This is the core differentiator. The composer is Markdown-first with embedded commands.

- [x] **4.1 — Create MarkdownService**
  - Parse Markdown to HTML via `league/commonmark` (or similar)
  - Extensions:
    - `@mention` → `<a class="mention" href="/users/{id}">@name</a>`
    - Fenced code blocks → syntax highlighted via `highlight.js` on frontend
    - `- [ ]` / `- [x]` → interactive checklists
    - Paste detection: stack traces auto-wrapped in code fence

- [x] **4.2 — Create SlashCommandService**
  - Parse ALL lines starting with `/` as commands (supports multi-command in one submit)
  - **Action commands** (execute ticket changes):
    - `/assign @username` — change assignee
    - `/status <name>` — change status (fuzzy match: "testing", "closed", "in progress")
    - `/estimate <number>` — set story points
    - `/hours <number>` — log time
    - `/close` — close ticket (shortcut for `/status closed`)
    - `/reopen` — reopen ticket
    - `/priority <level>` — change importance
    - `/milestone <name>` — change milestone
    - `/pin` — pin this comment
  - **Signal commands** (set note type for visual hierarchy):
    - `/decision <text>` — marks note as `decision` type (highlighted, always scannable)
    - `/blocker <text>` — marks note as `blocker` type (red/attention styling)
    - `/update <text>` — marks note as `update` type (distinct from casual comments)
    - `/action <text> @assignee` — marks note as `action` type (next thing that needs to happen)
  - **`/action` quality enforcement (hard constraints):**
    - Require exactly one `@assignee` — reject if missing or if multiple mentions
    - Error: "Actions require exactly one @assignee"
    - Max 3 unresolved `/action` notes per ticket — if exceeded, reject with: "Too many open actions. Resolve or overwrite an existing action before creating a new one"
    - This prevents Pulse from becoming a noisy backlog of abandoned actions
    - **Auto-assign rule:** if ticket is unassigned AND `/action @user` is created → auto-assign ticket to the action assignee. Ownership and responsibility must not drift apart.
  - **`/decision` integrity (immutable once created):**
    - Decisions cannot be silently edited. Editing a `decision` note creates a **new** decision note with `supersedes_id` pointing to the original
    - Original marked as "Superseded" (visual strikethrough, muted) with link to replacement
    - This preserves the audit trail — you can always trace how a decision evolved
    - Promotion to decision also follows minimum length rule (see 4.3)
  - **Blocker expiration (soft enforcement):**
    - If a blocker note is > 48 hours old and unresolved → Pulse shows warning: "Blocker may be stale (2+ days)"
    - `text-warning` styling, not blocking — just visibility
    - Prevents blockers from becoming permanent dead weight that everyone ignores
  - **Blocker enforcement:**
    - If active blocker exists, `/status` commands are rejected with error: "Resolve blocker before changing status"
    - Only resolving the blocker (via Pulse [Resolve] or editing the note) unlocks status changes
    - `/close` also blocked while blocker is active
  - **Multi-command parsing** — this is a key differentiator:
    ```
    /assign @john
    /status testing
    /action Verify fix on staging @sarah
    Found the root cause in the payment handler.
    ```
    One submit → three state changes + one comment. Replaces clicking assignee, clicking status, writing comment, creating follow-up task.
  - Signal commands set `notetype` on the note; the text after the command becomes the note body
  - Non-command lines become the note body
  - Commands execute the action AND create changelog entries
  - Return structured result array: `[{ action: 'assigned', to: 'john' }, { action: 'status_changed', to: 'Testing' }, ...]`
  - **Unknown commands:** flag as warning, don't silently swallow
    - Action preview bar shows: "⚠ Unknown command: `/statsu` — will be treated as text"
    - User can still submit — warning is non-blocking
    - Prevents typos from silently failing (e.g. `/statsu testing` → user thinks status changed, it didn't)
    - Silent failure destroys trust in the command system

- [x] **4.3 — Promote to signal (from any comment, with friction)**
  - From the kebab menu (⋯) on any `message` type note:
    - "Promote to Decision"
    - "Promote to Blocker"
    - "Promote to Action"
  - **Confirmation required:** modal dialog: "This will surface in Ticket Pulse. All team members will see it."
  - **Decision minimum length:** note body must be >= 20 characters to promote to decision. Rejects "ok let's do it" — decisions must be substantive
  - **Action promotion:** prompts for `@assignee` if none present in the note body (same validation as `/action`)
  - Changes `notetype` on the existing note — no editing required
  - Pulse updates immediately via cache invalidation
  - Preserves the "derived, not entered" philosophy — signal emerges from conversation, but with enough friction to prevent garbage in Pulse

- [x] **4.4 — Create MentionService**
  - `parseMentions(string $markdown): array` — find `@username` patterns
  - `createMentions(Note $note, array $userIds): void` — bulk insert
  - Deduplicate with watchers (don't double-notify)

- [x] **4.5 — Integrate into TicketService::notate()**
  - Before saving: run SlashCommandService to extract and execute commands
  - Parse Markdown to HTML, store both `body_markdown` and `body`
  - Parse and create mentions
  - Fire notifications

### Phase 5: Notifications & Activity Center

- [x] **5.1 — Create MentionNotification**
  - Channels: `['mail', 'database']`
  - `toMail()`: "{User} mentioned you in Ticket #{id}"
  - `toArray()`: structured data for Activity Center

- [x] **5.2 — Create ReplyNotification**
  - Fires when someone replies to your comment
  - Channels: `['mail', 'database']`
  - Deduplicate: if already notified as watcher, skip

- [x] **5.3 — Update WatcherNotification**
  - Add `database` channel alongside `mail`
  - Include structured `toArray()` for Activity Center

- [x] **5.4 — Notification batching**
  - Don't fire individual emails for rapid activity
  - Batch: collect notifications for 5 minutes, send digest
  - Database notifications still instant (for Activity Center)
  - Per-ticket mute: `ticket_user_watchers` gets `muted` boolean column

- [x] **5.5 — Create ActivityController**
  - `GET /activity` — Activity Center page
  - Query Laravel `notifications` table for current user
  - Filters: All | Mentions | Watching | Assigned | Replies
  - `POST /activity/read/{id}` — mark one as read
  - `POST /activity/read-all` — mark all as read

- [x] **5.6 — Create `activity/index.blade.php`**
  - Layout:
    ```
    ┌──────────────────────────────────────────────┐
    │ Activity Center                  Mark all ✓  │
    ├──────────────────────────────────────────────┤
    │ All │ Mentions │ Watching │ Replies           │
    ├──────────────────────────────────────────────┤
    │ 🔴 @sarah mentioned you in #142              │
    │    "...@you can you check the deploy?"       │
    │    5 minutes ago                              │
    ├──────────────────────────────────────────────┤
    │ ⚪ Ticket #98 → Closed                       │
    │    You are watching this ticket               │
    │    2 hours ago                                │
    └──────────────────────────────────────────────┘
    ```

- [x] **5.7 — Navbar notification bell**
  - Bell icon with unread count badge (red dot if > 0)
  - Click → dropdown with latest 5
  - "View all" → `/activity`

### Phase 6: Routes & Controllers

- [x] **6.1 — Reaction routes**
  - `POST /notes/{id}/react` — `NotesController@toggleReaction`
  - Body: `{ emoji: 'thumbsup' }`, validate against allowed set
  - Returns: `{ reactions: { thumbsup: { count: 3, reacted: true }, ... } }`

- [x] **6.2 — Reply route**
  - `POST /notes/reply` — `NotesController@reply`
  - Accepts: `ticket_id`, `parent_id`, `body` (Markdown)
  - Validates: parent belongs to same ticket, parent is top-level
  - Runs Markdown + mention parsing
  - Returns JSON for AJAX append

- [x] **6.3 — Edit route**
  - `PUT /notes/{id}` — `NotesController@update`
  - Author-only, sets `edited_at`
  - Re-parses Markdown and mentions
  - **Decision immutability:** if `notetype === 'decision'`, editing is blocked. Instead, user is prompted to create a new decision that supersedes the original. New note gets `supersedes_id` pointing to original. Original marked as superseded.
  - Returns JSON: `{ body, body_markdown, edited_at }`

- [x] **6.4 — Pin/Resolve routes**
  - `POST /notes/{id}/pin` — `NotesController@togglePin`
  - `POST /notes/{id}/resolve` — `NotesController@resolve`
  - **Resolve constraints:**
    - Only thread **author or ticket assignee** can resolve (403 otherwise)
    - Requires `resolution_message` in request body — not a checkbox toggle
    - Resolution message becomes a reply on the thread explaining the outcome
    - Sets `resolved_by`, `resolution_message`, `resolved` on the parent note
  - Returns JSON

- [x] **6.5 — Attachment routes**
  - `POST /notes/upload` — upload during composition (before note saved)
  - `POST /notes/{id}/attachments` — attach to existing note
  - Validates: image/pdf/zip/txt/log, max 10MB
  - Store in `storage/app/public/attachments/{ticket_id}/`
  - Returns JSON: `{ id, filename, url, mime_type, isImage }`

- [x] **6.6 — Presence route**
  - `POST /tickets/{id}/presence` — heartbeat (called every 15s by JS)
  - `GET /tickets/{id}/presence` — who's currently viewing
  - Store in cache (Redis/file): `ticket:{id}:viewers` → `{ user_id, name }`
  - Expire after 30 seconds of no heartbeat
  - Returns: `{ viewers: [{name}], count: 2 }`

- [x] **6.7 — Update TicketsController@show**
  - Eager load unified timeline: `notes.user`, `notes.replies.user`, `notes.reactions`, `notes.attachments`, `notes.mentions`
  - Load ALL notes (messages + changelog) ordered by `created_at`
  - Calculate `$lastViewedAt` for unread divider
  - Pass `$allUsers` JSON for @mention autocomplete
  - Pass `$pinnedNotes` separately for top display

### Phase 7: Frontend — Unified Timeline

- [x] **7.1 — Create `partials/activity-timeline.blade.php`**
  - Replaces the two-tab layout entirely
  - Structure:
    ```
    ┌──────────────────────────────────────────────┐
    │ [All] [Comments] [Decisions] [Blockers] [Activity]  ↕│
    ├──────────────────────────────────────────────┤
    │ 📌 Pinned: "Decision: we're using Redis..."  │
    ├──────────────────────────────────────────────┤
    │ ... timeline entries ...                      │
    │                                               │
    │ ──── New since your last visit ────           │
    │                                               │
    │ ... newer entries ...                          │
    ├──────────────────────────────────────────────┤
    │ [↓ Latest] [● Unread] [📌 Pinned] [⚠ Unresolved] │
    └──────────────────────────────────────────────┘
    ```
  - Filter buttons: pure JS, toggle `data-entry-type`, persist in localStorage
  - Pinned notes always visible at top regardless of filter

- [x] **7.2 — Create `partials/comment-card.blade.php`**
  - Social feed layout adapted to BS 5.3:
    ```
    [avatar]  Username                    5m ago ⋯
              ┌──────────────────────────────────┐
              │ Markdown-rendered comment body    │
              │ with @mentions highlighted and    │
              │ syntax-highlighted code blocks    │
              │                                  │
              │ 📎 screenshot.png (inline)        │
              └──────────────────────────────────┘
              [👍3] [👀2]  · Reply
              Edited by Username on Mar 21, 2026
              ┌── 2 replies ─────────────────────┐
              │ [av] Sarah  2m                   │
              │      Reply content... [👍1]       │
              │ [av] John   1m        ✓ Resolved │
              │      Fixed in abc123              │
              └──────────────────────────────────┘
              [Reply: type here... (Markdown)]
    ```
  - **Signal type styling** (based on `notetype`):
    - `message` (default) — standard `bg-body-tertiary` bubble
    - `decision` — left border accent (`border-start border-4 border-success`), "Decision" badge in header
    - `blocker` — left border accent (`border-start border-4 border-danger`), "Blocker" badge in header, stays visible in all filters
    - `update` — left border accent (`border-start border-4 border-info`), "Update" badge in header
    - `action` — left border accent (`border-start border-4 border-warning`), "Action" badge in header, shows assigned person
    - `changelog` — uses separate compact template (7.3)
  - Reactions: **visually quiet** — small pills below content, muted color until hovered
  - Edited indicator: small muted "Edited by {author} on {date}" below body
  - Resolved threads: collapsed with "Resolved" badge, click to expand
  - Kebab menu (⋯): Edit (own only), Pin, Resolve, Hide
  - Hours badge if > 0
  - Attachments: inline image thumbnails, file icon + name for non-images
  - `@mentions` styled: `color: var(--bs-primary); font-weight: 600;`

- [x] **7.3 — Create `partials/changelog-entry.blade.php`**
  - Compact, distinct from comments:
    ```
    [avatar]  Username changed ticket          2h ago
              • Status: Open → In Progress
              • Assignee: Unassigned → Sarah
    ```
  - Smaller text, `text-muted`, `fas fa-history` icon
  - No reactions, no replies, no kebab menu
  - Collapsible if multiple consecutive changelog entries

- [x] **7.4 — Create `partials/reaction-bar.blade.php`**
  - **v2.1:** two inline buttons only, no picker:
    ```
    [👍 3] [👀 2]
    ```
  - Each: `btn-sm rounded-pill` with muted border
  - Highlighted if current user reacted (filled background)
  - Click toggles reaction directly — no popover, no `[+]` button
  - Never competes visually with the comment text
  - v2.2: expand to full emoji set with picker popover

- [x] **7.5 — Create `components/avatar.blade.php`**
  - `<x-avatar :user="$user" :size="46" />`
  - Outputs `<img class="rounded-circle" ...>` with Gravatar fallback
  - Consistent sizing across all uses

### Phase 8: Frontend — Composer & Interactions

- [x] **8.1 — Markdown composer (replaces Quill)**
  - Default input is a **textarea with live Markdown preview**
  - Split layout: type left, preview right (or toggle)
  - Features:
    - Toolbar: Bold, Italic, Code, Link, List, Checklist, Attach (minimal)
    - `@` triggers mention autocomplete dropdown
    - `/` at start of line triggers slash command autocomplete
    - Paste image → auto-upload, insert `![](url)`
    - Paste URL → auto-embed as link (unfurl title if possible)
    - Paste stack trace → auto-wrap in ` ```  ``` `
    - `Cmd+Enter` to submit
  - Fallback: Quill available as toggle for non-technical users ("Rich Editor" switch)
  - Below editor: collapsible "Status & Time" row (status dropdown + hours input)
  - **Action preview bar:** when commands are detected, show parsed actions before submit:
    ```
    ┌─ Actions ─────────────────────────────────────┐
    │ ✓ Assign to @john                             │
    │ ✓ Status → Testing                            │
    │ ✓ Action: Verify fix on staging → @sarah      │
    └───────────────────────────────────────────────┘
    ```
    This confirms what will happen before the user submits. Builds trust in multi-command.
  - Structure:
    ```
    ┌──────────────────────────────────────────────┐
    │ [avatar]  [Markdown textarea          ] [👁]  │
    │           [/assign @john — auto-suggest]      │
    │           ┌─ Actions ─────────────────────┐  │
    │           │ ✓ Assign to @john             │  │
    │           │ ✓ Status → Testing            │  │
    │           └───────────────────────────────┘  │
    │           ┌─ Status & Time (click to show) ─┐│
    │           │ [Status ▼] [Hours ___]           ││
    │           └─────────────────────────────────┘│
    │                        [? Help] [Post Update ▶]│
    └──────────────────────────────────────────────┘
    ```

- [x] **8.2 — Composer help reference**
  - Small `[? Help]` link in composer footer, next to submit button
  - Opens a modal or slide-out panel with:
    - Markdown syntax cheat sheet (bold, italic, code, lists, checklists, links, images)
    - Slash command reference table: command, description, example
    - @mention usage
    - Keyboard shortcuts (`Cmd+Enter`, `Escape`, etc.)
    - Signal types explained: what `/decision`, `/blocker`, `/action` do and when to use them
  - Also accessible via `?` keyboard shortcut (Phase 9)
  - Content lives in a Blade partial: `partials/composer-help.blade.php`

- [x] **8.3 — Slash command autocomplete**
  - Type `/` at start of a new line → show command palette dropdown
  - List: `/assign`, `/status`, `/estimate`, `/hours`, `/close`, `/reopen`, `/priority`, `/pin`
  - Each shows description and accepts inline args
  - Highlighted current selection, arrow keys + enter to select
  - After selecting, cursor placed for argument input

- [x] **8.4 — @Mention autocomplete**
  - Type `@` → dropdown of users filtered by typing
  - Data source: `$allUsers` passed as JSON
  - Arrow keys + enter/tab to select
  - Inserts `@username` text (parsed server-side to links)

- [x] **8.5 — Inline reply composer**
  - Click "Reply" → expand Markdown textarea below comment (not Quill)
  - Smaller, focused: just text + mention support + `Cmd+Enter`
  - Submit via AJAX → append to thread without reload

- [x] **8.6 — Inline editing**
  - Click "Edit" → swap rendered body with Markdown textarea (pre-filled from `body_markdown`)
  - Save → `PUT /notes/{id}` via AJAX → re-render in place
  - Show "Edited by {author} on {date}" text below the note body — no hover, no diff
  - Cancel → restore original
  - **Decision notes:** "Edit" button replaced with "Update Decision" → opens composer prefilled with `/decision` + original text. Creates new superseding note. Cannot inline-edit decisions.

- [x] **8.7 — Attachment handling (v2.1: simple upload)**
  - Click 📎 button → file picker → upload on note submit (not before)
  - Progress indicator during upload
  - Images: inline thumbnail preview after submit
  - Files: icon + filename + size
  - **v2.1 scope:** no drag-and-drop, no paste-to-upload, no pre-attach
  - v2.2: drag-and-drop, paste image, pre-attach before submit

### Phase 9: Keyboard Navigation

- [x] **9.1 — Global keyboard shortcuts (ticket show page)**
  - `r` — focus reply composer
  - `e` — edit your last comment
  - `↑` — when composer focused and empty, edit your most recent comment
  - `Cmd+Enter` / `Ctrl+Enter` — submit comment/reply
  - `Escape` — cancel editing, close reply form
  - `j` / `k` — navigate between comments (vim-style)
  - `p` — pin focused comment
  - `/` — focus composer with slash command mode

- [x] **9.2 — Keyboard shortcut help**
  - `?` opens modal with shortcut reference
  - Small "⌨" icon in corner linking to shortcuts

### Phase 10: Presence & Real-Time (Lightweight)

No websockets. Polling-based, 15-second interval.

- [x] **10.1 — Presence heartbeat (JS)**
  - Every 15 seconds: `POST /tickets/{id}/presence`
  - Response includes `{ viewers: [{name, avatar_url}], count: 2 }`
  - **Display: avatars only** — max 3 stacked avatar circles + "+N" count badge if more. No text sentence.
  - **Typing hint:** if user has focus in composer, heartbeat includes `{ composing: true }` → show subtle "…" indicator overlaid on their avatar. Prevents collisions without adding complexity. No "X is typing" text.
  - Shown in timeline header area, compact and unobtrusive
  - Store in cache with 30s TTL (auto-cleanup)

- [x] **10.2 — New activity polling**
  - Every 30 seconds: `GET /tickets/{id}/notes/since/{timestamp}`
  - If new notes exist, show banner: "3 new updates — Click to load"
  - Click → append new entries to timeline without full reload

### Phase 11: Signal Nudges (Gentle Heuristics)

Not AI. Not keyword matching. Simple structural heuristics that nudge users toward signal types.

- [x] **11.1 — Blocker keyword nudge (JS, composer-side)**
  - If message body contains "blocked", "waiting on", "depends on", "can't proceed" → show a subtle inline hint below composer:
    `"Tip: Use /blocker to make this visible in Ticket Pulse"`
  - Dismissable, non-blocking — user can ignore and submit normally
  - Only triggers on top-level notes, not replies

- [x] **11.2 — Long thread nudge**
  - When a thread reaches 5+ replies → show inline suggestion on the thread:
    `"This thread has grown. Convert to a decision?"`
  - Links to promote-to-signal action (kebab menu → "Promote to Decision")
  - One-time display per thread per user (track in localStorage)

- [x] **11.3 — Missing next action nudge**
  - If Ticket Pulse shows "No next action defined" AND the ticket is in an active status (not closed) → show a subtle nudge in the Pulse panel:
    `"Define what happens next: /action <description> @assignee"`
  - Muted text, not an alert — presence, not pressure

### Phase 12: Smart Paste Detection

- [x] **12.1 — Paste content detection (JS)**
  - On paste into composer, detect content type via regex:
    - Stack trace → auto-wrap in fenced code block
    - JSON → format and wrap in code block
    - URL → insert as Markdown link
  - Simple regex-based detection, no external dependencies

### Phase 13: Mobile Considerations

- [x] **13.1 — Responsive composer**
  - On small screens: full-width textarea, no side preview (toggle only)
  - Large tap targets on all buttons (min 44x44px)
  - Slash commands work but autocomplete is simplified

- [x] **13.2 — Collapsed threads**
  - Mobile: threads collapsed by default, show "N replies" tap to expand
  - Reactions: show counts only, tap to see who reacted

- [x] **13.3 — Simplified timeline**
  - Consecutive changelog entries auto-collapsed
  - Attachments: thumbnail grid instead of inline

### Phase 14: Notification Sanity

- [x] **14.1 — Batching**
  - Don't send individual emails for rapid-fire activity
  - Collect for 5 minutes, send digest email
  - Database notifications (Activity Center) remain instant

- [x] **14.2 — Per-ticket mute**
  - Add `muted` boolean to `ticket_user_watchers`
  - Muted = still watching (shows in Activity Center) but no email
  - UI: "Mute" option in ticket actions

- [x] **14.3 — Smart deduplication**
  - If user is both watcher AND mentioned: one notification, not two
  - If user is comment author AND watcher: don't notify about own comment
  - If replying to own thread: don't self-notify

- [x] **14.4 — Active viewer suppression**
  - If user has viewed the ticket within the last 2 minutes (check `ticket_views` or presence cache) → suppress email notification
  - They are already there. Email is redundant.
  - Database notification (Activity Center) still created — just no email
  - Uses presence heartbeat data when available, falls back to `ticket_views.updated_at`

### Phase 15: Testing

- [x] **15.1 — MarkdownService tests**
  - Parses standard Markdown correctly
  - @mentions converted to links
  - Code blocks syntax-highlighted wrapper
  - Checklists rendered as interactive HTML

- [x] **15.2 — SlashCommandService tests**
  - `/assign @user` changes assignee
  - `/status testing` changes status (fuzzy match)
  - `/close` sets closed_at and status
  - `/hours 2` logs time
  - Unknown commands passed through as text
  - Multiple commands in one note

- [x] **15.3 — MentionService tests**
  - Finds @username in plain text and Markdown
  - Handles: @nonexistent, duplicates, @mention inside code blocks (skip)
  - Creates mention records
  - Fires notifications (not to self)

- [x] **15.4 — Feature tests: reactions**
  - Toggle creates/removes
  - Validates against allowed emoji set
  - Returns grouped JSON
  - Auth required

- [x] **15.5 — Feature tests: replies**
  - Creates note with parent_id
  - Validates parent same ticket
  - Blocks nested replies (reply to reply)
  - Mentions parsed in replies
  - Reply notification sent to parent author

- [x] **15.6 — Feature tests: editing**
  - Author can edit own, non-author cannot
  - Sets edited_at, stores updated body_markdown
  - Re-parses mentions

- [x] **15.7 — Feature tests: pin/resolve**
  - Pin toggles correctly
  - Resolve sets resolved_by
  - Resolved threads shown correctly in timeline

- [x] **15.8 — Feature tests: attachments**
  - Upload stores file, validates type/size
  - Paste-upload flow
  - Attaches to correct note/ticket

- [x] **15.9 — Feature tests: slash commands (integration)**
  - Submit note with `/status testing` → status changes AND note created
  - Submit note with `/assign @user` → assignee changes AND changelog entry
  - Submit note with plain text → no side effects
  - `/decision text` → creates note with `notetype = decision`
  - `/blocker text` → creates note with `notetype = blocker`
  - `/update text` → creates note with `notetype = update`
  - `/action text @user` → creates note with `notetype = action`, mention parsed
  - `/action text` without @mention → rejected with error
  - `/action text @user @user2` with multiple mentions → rejected
  - 4th `/action` on a ticket with 3 unresolved → rejected
  - `/status` while active blocker exists → rejected
  - Unknown command `/statsu` → flagged as warning, treated as text
  - Unassigned ticket + `/action @user` → ticket auto-assigned to user

- [x] **15.10 — Feature tests: decision immutability**
  - Edit attempt on `decision` note → blocked, returns error
  - "Update decision" flow → creates new note with `supersedes_id`
  - Original decision marked as superseded
  - Pulse shows latest non-superseded decision only

- [x] **15.11 — Feature tests: activity center**
  - Mention → notification appears
  - Watcher update → notification appears
  - Reply → notification to parent author
  - Mark read, mark all read
  - Deduplication works

- [x] **15.12 — Feature tests: Ticket Pulse**
  - Pulse returns correct status (including BLOCKED override when active blocker exists)
  - Execution State derived correctly: ON TRACK, AT RISK, BLOCKED, IDLE
  - Ownership clarity: "You own this" vs "Owner: X" vs "Unassigned"
  - Next action sourced from latest `/action` note
  - Latest decision sourced from latest non-superseded `/decision` note
  - Superseded decisions not shown as "latest"
  - Blocker stale warning after 48h
  - Open threads listed by name with reply counts
  - Staleness warning after 48h of no activity
  - Cache invalidated on note create/edit/hide, status change, assignee change

- [x] **15.13 — Feature tests: thread resolution**
  - Only thread author or ticket assignee can resolve
  - Resolution requires message — empty message rejected
  - Resolution message appears as reply on thread
  - Non-author/non-assignee gets 403

- [x] **15.14 — Feature tests: presence**
  - Heartbeat stores viewer in cache
  - Expired viewers removed after 30s

### Phase 16: API Integration

The REST API (`/api/v1/`) predates the comment system. It currently returns flat note data (id, user, body, hours, created_at) with no awareness of signal types, threading, reactions, attachments, mentions, slash commands, or Ticket Pulse. This phase brings the API to parity with the web interface.

- [x] **16.1 — Update `GET /api/v1/tickets/{id}` response shape**
  - Eager load full note tree: `notes.user`, `notes.replies.user`, `notes.reactions`, `notes.attachments`, `notes.mentions.user`
  - Each note in the response includes all new fields:
    ```json
    {
      "id": 42,
      "user": { "id": 1, "name": "Sarah" },
      "body": "We decided to use Redis",
      "body_markdown": "<p>We decided to use Redis</p>",
      "notetype": "decision",
      "hours": 0,
      "pinned": false,
      "edited_at": null,
      "resolved": false,
      "resolved_by": null,
      "resolution_message": null,
      "parent_id": null,
      "supersedes_id": null,
      "created_at": "2026-03-22 10:00:00",
      "reactions": { "thumbsup": { "count": 3, "reacted": false }, "eyes": { "count": 1, "reacted": true } },
      "replies": [ { "id": 43, "user": { "id": 2, "name": "John" }, "body": "Agreed", "created_at": "..." } ],
      "attachments": [ { "id": 1, "filename": "screenshot.png", "url": "/storage/...", "mime_type": "image/png", "is_image": true } ],
      "mentions": [ { "id": 1, "user": { "id": 2, "name": "John" } } ]
    }
    ```
  - Filter: top-level notes only in the main `notes` array (replies nested under their parent)
  - Preserve existing `hide = 0` filter
  - Order by `created_at asc` (matches unified timeline)

- [x] **16.2 — Add `pulse` object to `GET /api/v1/tickets/{id}` response**
  - Include Ticket Pulse alongside ticket data:
    ```json
    {
      "data": {
        "id": 1,
        "subject": "...",
        "pulse": {
          "status": "BLOCKED",
          "execution_state": "BLOCKED",
          "is_blocked": true,
          "blocker_reason": "Waiting on API key from vendor",
          "latest_blocker": { "id": 15, "body": "...", "author": "Sarah", "created_at": "..." },
          "latest_decision": { "id": 12, "body": "We'll use Redis", "author": "John", "created_at": "...", "supersedes": null },
          "next_action": { "id": 18, "body": "/action Verify fix on staging", "assignee": "@sarah", "created_at": "..." },
          "open_threads": [ { "id": 5, "subject": "Should we cache this?", "reply_count": 3 } ],
          "owner_label": "Waiting on: @vendor",
          "is_stale": false,
          "staleness_message": null,
          "last_activity_at": "2026-03-22T10:30:00Z"
        },
        "notes": [ ... ]
      }
    }
    ```
  - Uses `TicketPulseService::getPulse()` — same cached, deterministic data as the web UI
  - API consumers can check `pulse.is_blocked`, `pulse.execution_state`, `pulse.latest_decision` without parsing notes themselves

- [x] **16.3 — Add signal-type filters to `GET /api/v1/tickets/{id}`**
  - Optional query params to filter notes by type:
    - `?notetype=decision` — only decision notes
    - `?notetype=blocker` — only blocker notes
    - `?notetype=action` — only action notes
    - `?notetype=changelog` — only changelog entries
    - `?notetype=message` — only message notes (default behavior if omitted = all)
  - Useful for integrations that only care about decisions or blockers (e.g., a dashboard)

- [x] **16.4 — Update `POST /api/v1/tickets/{id}/note` to run the command pipeline**
  - Currently creates a bare `message` note. Must now:
    1. Run `SlashCommandService::handle()` on the body text
    2. Apply slash command side effects (status change, assignee change, hours, etc.)
    3. Run `MarkdownService::parse()` on the remaining body to produce `body_markdown`
    4. Run `MentionService::parse()` to create mention records and queue notifications
    5. Set `notetype` from slash command results (decision, blocker, action, etc.)
    6. Set `pinned` from slash command results (`/pin`)
    7. Return the created note in the new expanded format (matching 16.1 shape)
  - Reject `/action` without @mention (422), `/action` with multiple @mentions (422), 4th action on ticket with 3 unresolved (422), `/status` while active blocker (422) — same constraints as web
  - Return `warnings` array from slash command service (unknown commands, etc.)
  - Response shape:
    ```json
    {
      "message": "Note added successfully",
      "warnings": ["/statsu is not a recognized command — treated as text"],
      "ticket": { "id": 1, "status": "Testing", "assignee": "Sarah" },
      "note": {
        "id": 50,
        "notetype": "decision",
        "body": "We'll use Redis for caching",
        "body_markdown": "<p>We'll use Redis for caching</p>",
        "pinned": false,
        "reactions": {},
        "replies": [],
        "attachments": [],
        "mentions": [{ "id": 1, "user": { "id": 3, "name": "John" } }]
      }
    }
    ```

- [x] **16.5 — Add `POST /api/v1/tickets/{id}/notes/{noteId}/react` endpoint**
  - Toggle reaction on a note (same logic as web `NotesController@toggleReaction`)
  - Body: `{ "emoji": "thumbsup" }` — validates against `NoteReaction::ALLOWED_EMOJIS`
  - Returns: `{ "reactions": { "thumbsup": { "count": 3, "reacted": true } } }`

- [x] **16.6 — Add `POST /api/v1/tickets/{id}/notes/{noteId}/reply` endpoint**
  - Create a threaded reply (same logic as web `NotesController@reply`)
  - Body: `{ "body": "Agreed, Redis is the way to go" }`
  - Validates: parent belongs to same ticket, parent is top-level (no nested replies)
  - Runs Markdown + mention parsing on reply body
  - Returns: the created reply note in expanded format

- [x] **16.7 — Add `PUT /api/v1/tickets/{id}/notes/{noteId}` endpoint**
  - Edit a note (same constraints as web `NotesController@update`)
  - Author-only (403 otherwise)
  - **Decision immutability:** if `notetype === 'decision'`, returns 422 with message explaining decisions cannot be edited — must create a new decision that supersedes
  - Sets `edited_at`, re-parses Markdown and mentions
  - Returns: the updated note in expanded format

- [x] **16.8 — Add `POST /api/v1/tickets/{id}/notes/{noteId}/resolve` endpoint**
  - Resolve a thread (same constraints as web `NotesController@resolve`)
  - Body: `{ "resolution_message": "Fixed in commit abc123" }`
  - Only thread author or ticket assignee can resolve (403 otherwise)
  - Requires non-empty `resolution_message` (422 otherwise)
  - Returns: the updated parent note with `resolved: true`

- [x] **16.9 — Add `GET /api/v1/tickets/{id}/pulse` endpoint**
  - Dedicated lightweight endpoint for just the Pulse data
  - Returns `TicketPulseService::getPulse()` directly — same shape as the `pulse` key in 16.2
  - Useful for dashboards and status boards that poll across many tickets without loading full note trees
  - Cached — cheap to call frequently

- [x] **16.10 — Update `GET /api/v1/tickets` index to include signal summary**
  - Add optional `?include=pulse` query param
  - When included, each ticket in the list gets a compact signal summary:
    ```json
    {
      "id": 1,
      "subject": "Fix login bug",
      "status": "In Progress",
      "importance": "High",
      "pulse_summary": {
        "execution_state": "BLOCKED",
        "is_blocked": true,
        "has_open_actions": true,
        "has_decisions": true,
        "unresolved_thread_count": 2
      }
    }
    ```
  - Without `?include=pulse`, response is unchanged (backward-compatible)
  - Summary is lighter than full Pulse — no note bodies, just booleans and counts

- [x] **16.11 — Update `TicketResource` for the `fetch` endpoint**
  - `TicketsController@fetch` uses `TicketResource` which returns raw IDs only
  - Add: `status_name`, `assignee_name`, `note_count`, `notetype` summary counts
  - Backward-compatible: new fields are additive

- [x] **16.12 — API route registration and versioning**
  - All new endpoints under `/api/v1/` prefix with `api.token` middleware
  - Routes:
    ```
    GET    /api/v1/tickets                          (updated: ?include=pulse)
    GET    /api/v1/tickets/{id}                     (updated: notes + pulse)
    GET    /api/v1/tickets/{id}/pulse               (new)
    POST   /api/v1/tickets/{id}/note                (updated: command pipeline)
    POST   /api/v1/tickets/{id}/notes/{noteId}/react   (new)
    POST   /api/v1/tickets/{id}/notes/{noteId}/reply   (new)
    PUT    /api/v1/tickets/{id}/notes/{noteId}         (new)
    POST   /api/v1/tickets/{id}/notes/{noteId}/resolve (new)
    ```
  - Apply `throttle:api` to all endpoints

- [x] **16.13 — API tests for all new/updated endpoints**
  - Test `show` returns full note shape with notetype, reactions, replies, attachments, mentions
  - Test `show` returns pulse object with execution_state, is_blocked, latest_decision, latest_blocker
  - Test `show` with `?notetype=decision` filters to decisions only
  - Test `note` with `/decision text` creates decision note via API
  - Test `note` with `/blocker text` creates blocker note via API
  - Test `note` with `/action @user text` creates action + auto-assigns
  - Test `note` with `/action text` (no mention) returns 422
  - Test `note` with `/status testing` while active blocker returns 422
  - Test `note` returns `warnings` for unknown commands
  - Test `react` toggles reaction and returns grouped counts
  - Test `reply` creates threaded reply, rejects nested reply
  - Test `edit` sets edited_at, blocks decision editing (422)
  - Test `resolve` requires message, enforces author/assignee-only
  - Test `pulse` endpoint returns cached Pulse data
  - Test `index` with `?include=pulse` returns signal summary
  - Test backward compatibility: existing API consumers get same shape + additive fields

---

## Files to Create

| File | Purpose |
|------|---------|
| **Migrations** | |
| `xxxx_add_threading_fields_to_notes_table.php` | parent_id, edited_at, pinned, resolved, resolved_by, supersedes_id, resolution_message, body_markdown |
| `xxxx_create_note_reactions_table.php` | Emoji reactions |
| `xxxx_create_note_attachments_table.php` | File attachments |
| `xxxx_create_mentions_table.php` | @mention records |
| `xxxx_add_avatar_to_users_table.php` | User avatars |
| `xxxx_add_muted_to_ticket_user_watchers.php` | Per-ticket mute |
| `xxxx_expand_notetype_enum_on_notes.php` | Add decision, blocker, update, action types |
| `xxxx_create_notifications_table.php` | Laravel notifications (if missing) |
| **Models** | |
| `app/Models/NoteReaction.php` | Reaction model |
| `app/Models/NoteAttachment.php` | Attachment model |
| `app/Models/Mention.php` | Mention model |
| **Services** | |
| `app/Services/TicketPulseService.php` | Computes and caches Ticket Pulse data |
| `app/Services/MarkdownService.php` | Markdown → HTML with extensions |
| `app/Services/SlashCommandService.php` | Parse and execute `/commands` |
| `app/Services/MentionService.php` | @mention parsing and notification |
| `app/Services/PresenceService.php` | Viewer tracking via cache |
| **Notifications** | |
| `app/Notifications/MentionNotification.php` | @mention notification |
| `app/Notifications/ReplyNotification.php` | Reply to your comment |
| **Controllers** | |
| `app/Http/Controllers/ActivityController.php` | Activity Center |
| **Views** | |
| `resources/views/partials/ticket-pulse.blade.php` | Ticket Pulse panel (primary interface) |
| `resources/views/partials/activity-timeline.blade.php` | Unified timeline |
| `resources/views/partials/comment-card.blade.php` | Comment component |
| `resources/views/partials/changelog-entry.blade.php` | Activity entry |
| `resources/views/partials/reaction-bar.blade.php` | Reaction pills |
| `resources/views/partials/markdown-composer.blade.php` | Composer component |
| `resources/views/partials/composer-help.blade.php` | Help reference modal content |
| `resources/views/components/avatar.blade.php` | Avatar component |
| `resources/views/activity/index.blade.php` | Activity Center page |

## Files to Modify

| File | Changes |
|------|---------|
| `app/Models/Note.php` | Threading, reactions, attachments, mentions, pin, resolve |
| `app/Models/User.php` | Avatar, avatarUrl(), mentions relationship |
| `app/Http/Controllers/NotesController.php` | Reactions, reply, edit, pin, resolve, attachments |
| `app/Http/Controllers/TicketsController.php` | Unified timeline eager loading, presence |
| `app/Services/TicketService.php` | Markdown parsing, slash commands, mentions in notate() |
| `app/Notifications/WatcherNotification.php` | Add database channel |
| `routes/web.php` | All new routes |
| `resources/views/tickets/show.blade.php` | Replace tabs with unified timeline + new composer |
| `resources/views/layouts/app.blade.php` | Navbar notification bell |

## Release Scope

### v2.1.0 — Ships in this release

The minimum viable work surface. Everything here is required for the system to function as designed.

**Core engine:**
- Ticket Pulse — full primary interface (Phase 3)
- Execution State: ON TRACK / AT RISK / BLOCKED / IDLE (Phase 3.2)
- Slash commands with multi-command parsing (Phase 4)
- Signal note types: decision, blocker, update, action (Phase 1.2, 4.2)
- Signal integrity constraints: `/action` validation, active limits, blocker expiration (Phase 4.2)
- Decision immutability with supersede chain (Phase 4.2, 6.3)
- Blocker enforcement — hard rule (Phase 3.2, 4.2)
- Unknown command warnings in action preview bar (Phase 4.2)
- Auto-assign from `/action` on unassigned tickets (Phase 4.2)

**Composer & interaction:**
- Markdown composer with live preview (Phase 8.1)
- Action preview bar for multi-command (Phase 8.1)
- Slash command autocomplete (Phase 8.3)
- @mention autocomplete (Phase 8.4)
- Composer help reference (Phase 8.2)
- `Cmd+Enter` to submit (Phase 9.1)

**Timeline & display:**
- Unified timeline replacing two-tab layout (Phase 7.1)
- Comment cards with signal type styling (Phase 7.2)
- Compact changelog entries (Phase 7.3)
- 1-level threading with enforced resolve (author/assignee + message) (Phase 6.2, 6.4)
- Basic editing with "Edited" indicator (Phase 6.3)
- Pin/resolve (Phase 6.4)
- Promote to signal with friction (Phase 4.3)
- Reactions: 👍 and 👀 only, no picker (Phase 7.4)
- Simple file upload on submit (Phase 8.7)

**Notifications & awareness:**
- @mentions with notifications (Phase 4.4, 5.1)
- Activity Center (Phase 5.5, 5.6)
- Navbar notification bell (Phase 5.7)
- New activity polling with "New updates" banner (Phase 10.2)
- Active viewer email suppression (Phase 14.4)
- Signal nudges — blocker keyword, long thread, missing action (Phase 11)

**Database:**
- All Phase 1 migrations (threading, signal types, reactions, attachments, mentions, avatar, supersedes_id, resolution_message)

### v2.2.0 — Deferred

- Full emoji reaction set + picker popover
- Advanced attachments: drag-and-drop, paste-to-upload, pre-attach
- Smart paste detection (stack traces, JSON, URLs)
- Presence UI polish (composing indicator, typing hints)
- Keyboard shortcuts beyond `Cmd+Enter` and `Escape` (j/k, r, e, p)
- Notification batching (5-minute email digests)
- Mobile-specific optimizations (collapsed threads, simplified timeline)
- Per-ticket mute

---

## Design Decisions

- **Ticket Pulse is the primary interface** — the timeline is the detail view. Pulse surfaces meaning (status, blocker, next action, latest decision, open threads) so devs comprehend state in under 10 seconds without scrolling. Deterministic, cached, instant — no reconstruction from history
- **Pulse is interactive, not passive** — every item has actions (Resolve blocker, Update decision, Resolve thread). It's a control surface, not a dashboard
- **Blocked overrides status** — if an active blocker exists, Pulse shows BLOCKED regardless of the actual status field. Don't split truth across fields
- **Markdown-first, Quill optional** — devs want speed, not rich text toolbars. Toggle available for non-technical users
- **Slash commands** — the #1 differentiator. No dropdown hunting. `/status testing` is faster than any UI
- **1-level threading** — Jira-style, prevents unreadable nesting. Threads can be resolved (borrowed from code review)
- **No quote-reply** — redundant with threading + resolved threads. Adds complexity without gain
- **2 reactions for v2.1 (👍, 👀)** — full set is noise. Two covers "acknowledged" and "looking at it". No picker, just inline toggle buttons. Expand in v2.2
- **Reactions visually quiet** — never compete with comment text. Small, muted pills
- **Changelog entries are read-only** — no reactions/replies on system activity
- **Hours on top-level only** — replies are conversation, not time tracking
- **Polling not websockets for v2.1** — 15s presence, 30s new content. Avoids infrastructure complexity. Can add Reverb/Pusher later
- **Gravatar-only for v2.1** — no avatar upload flow. Covers most dev teams
- **Edit indicator is plain text** — "Edited by {author} on {date}" below the body. No hover, no diff, no history viewer
- **Signal types over flat comments** — not all comments are equal. `/decision`, `/blocker`, `/update` give structural hierarchy so the timeline is scannable in 3 seconds. Decisions highlighted, blockers red, updates accented — everything else is default
- **No auto-suggest status changes** — keyword matching misfires constantly; bad suggestions feel worse than none. Slash commands (`/status`, `/close`) already cover this use case explicitly
- **Notification batching** — 5-minute email digest prevents spam. Database notifications stay instant for Activity Center
- **Blocker enforcement is a hard rule** — active blocker blocks ALL status transitions (`/status`, `/close`, UI dropdown). Resolving the blocker unlocks status changes. Without this, Pulse and status diverge and trust breaks
- **Next Action strict sourcing** — only from `/action` notes and checklist items, never from threads. Threads are discussions, not commitments. Including them makes Pulse noisy and unreliable
- **Multi-command is a killer feature** — one submit can assign, change status, set an action, and leave a comment. Action preview bar confirms what will happen before submit. This replaces 4 clicks with 4 lines
- **Promote to signal from any comment** — kebab menu on any `message` note offers "Promote to Decision/Blocker/Action". Signal emerges from conversation, not forms. Pulse updates immediately via cache invalidation
- **Presence is avatars only** — max 3 stacked circles + "+N" count. No text sentence, no typing indicator. Presence should inform, not distract
- **Signal nudges are heuristics, not AI** — "blocked" in text → suggest `/blocker`. 5+ thread replies → suggest "Convert to decision?". No next action → nudge in Pulse. Dismissable, non-blocking, zero false-positive cost
- **Simple attachments for v2.1** — upload on submit only. No pre-attach, no drag-drop, no paste-to-upload. These are polish, not core. v2.2 adds the fancy UX
- **Explicit v2.1 cut list** — ships: Pulse, slash commands, signal types, threading, basic timeline, mentions, Activity Center, activity polling. Defers: advanced reactions, fancy attachments, paste detection, keyboard shortcuts, mobile optimizations
- **Signal integrity is enforced, not hoped for** — `/action` requires exactly one assignee, max 3 active per ticket. `/decision` is immutable (edits create superseding notes). Blockers show stale warnings after 48h. Without hard constraints, Pulse becomes noise within weeks
- **Decision immutability preserves audit trail** — editing a decision creates a new note with `supersedes_id` pointing to the original. Original shown as superseded. You can always trace how a decision evolved. Silent edits destroy institutional memory
- **Execution State is derived, not entered** — ON TRACK / AT RISK / BLOCKED / IDLE computed from existing Pulse fields (has action? recent activity? active blocker?). This is what managers scan first. No new data entry required
- **Thread resolution requires context** — only author or assignee can resolve, must provide a resolution message. No silent checkbox toggles. Future readers need to know WHY a thread was resolved, not just that it was
- **Unknown slash commands warn, never silently fail** — `/statsu testing` shows warning in action preview bar, user can still submit. Silent failure destroys trust in the entire command system
- **Promotion requires friction** — confirmation modal + minimum 20 chars for decisions + assignee prompt for actions. Prevents "ok" becoming a decision and garbage flooding Pulse
- **Active viewer email suppression** — if user viewed ticket in last 2 minutes, skip email. They are already there. Reduces notification noise without losing Activity Center records
- **Composing indicator is subtle** — "..." overlay on presence avatar when user has composer focus. Prevents duplicate work without Slack-level typing noise
- **Auto-assign from `/action`** — if ticket is unassigned and someone creates an action with `@user`, ticket auto-assigns to that user. Ownership and responsibility must stay coupled
