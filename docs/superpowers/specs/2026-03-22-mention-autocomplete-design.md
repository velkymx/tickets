# @Mention Autocomplete Redesign

## Problem

The current @mention system is broken for names with spaces. The regex `/@([\w.\-]+)/` only matches word characters, so `@John Smith` can never be parsed. The autocomplete dropdown HTML exists in `show.blade.php` but the JS wiring is incomplete — users have to manually type exact usernames.

## Solution

Replace the raw `@username` format with a bracketed token format `@[Name (Title)]` backed by a typeahead autocomplete dropdown. The title (job title from the User model) provides disambiguation and team context.

## Mention Format

- **Stored in markdown:** `@[John Smith (Developer)]` or `@[Jane Doe]` if title is null
- **Rendered HTML:** `<a class="mention" href="/users/5">@John Smith</a>` — title is for disambiguation in the editor only, not displayed in rendered output
- **Autocomplete dropdown items:** `John Smith (Developer)` — shows name + title

## Components

### 1. JS Autocomplete (resources/views/tickets/show.blade.php)

**Trigger:** Typing `@` in the note textarea opens the dropdown immediately, showing all users. Typing further filters the list.

**Data source:** The existing `data-users` attribute on `.markdown-composer`, expanded to include titles. Format: `[{id: 5, name: "John Smith", title: "Developer"}, ...]`

**Behavior:**
- Dropdown appears below the cursor position when `@` is typed — shows full user list immediately (matches slash command `/` behavior)
- Typing after `@` filters the list by name (case-insensitive substring match)
- Arrow keys navigate the list, Enter or click selects
- Selection inserts `@[Name (Title)]` at the cursor position in the textarea, replacing the `@` and any typed filter text
- If user has no title, inserts `@[Name]`
- Escape or clicking outside dismisses the dropdown

**Blade template update:** Remove the existing server-rendered `@foreach($allUsers ...)` dropdown items. The dropdown will be built dynamically from the `data-users` JSON by JS, since the data shape changes from `{id: name}` to `[{id, name, title}]`.

**Reuse:** The slash command autocomplete already exists with similar behavior. Follow the same pattern (`.mention-autocomplete` dropdown, `dropdown-item` buttons, keyboard nav).

### 2. MentionService::parseMentions() (app/Services/MentionService.php)

**Current regex:** `/(?<![\w])@([\w.\-]+)/u` — negative lookbehind prevents matching inside emails; only matches word chars, dots, hyphens. (Note: `MarkdownService` uses a simpler `/@([\w\.]+)/` without lookbehind — the new format resolves this inconsistency.)

**New regex:** `/@\[([^\]]+)\]/u` — captures everything inside `@[...]`. The lookbehind is no longer needed since `@[` is unambiguous and won't appear in emails. Both `MentionService` and `MarkdownService` use this same regex with the `/u` flag for Unicode name support.

**Semantic change:** Currently returns slug-style tokens (e.g., `john.smith`). New version returns full display names with spaces (e.g., `John Smith`). The caller (`TicketService`) resolves names to user IDs via `User::whereIn('name', $names)` and passes IDs to `createMentions()`. This contract change is intentional — callers already do name-based lookup.

**Parsing logic:**
1. Extract all `@[...]` tokens from markdown
2. For each token, strip trailing ` (Title)` parenthetical via regex: `/\s*\([^)]+\)$/`
3. Return array of bare names

**Name+title disambiguation:** When duplicate names exist, `parseMentions()` returns both. The caller resolves via `User::whereIn('name', ...)` which returns all matches — both users get mentioned. The title in the token is for the *human* writing the note to know who they're tagging, not for programmatic lookup. This is a deliberate simplification.

**Edge cases:**
- Unmatched names (typos, deleted users) are silently ignored — no mention created
- Duplicate names: all matching users get mentioned
- Names containing parentheses (e.g., `John Smith (Jr)`) — the title-stripping regex will incorrectly strip `(Jr)`. Known limitation; real-world occurrence is negligible

### 3. MarkdownService::replaceMentions() (app/Services/MarkdownService.php)

**Current regex:** `/@([\w\.]+)/` — same space problem.

**New regex:** `/@\[([^\]]+)\]/u` — matches bracketed tokens. Uses `/u` flag for Unicode consistency with MentionService.

**Important: Markdown parser interaction.** `Str::markdown()` (CommonMark) runs before `replaceMentions()`. Square brackets are Markdown link syntax and could mangle `@[Name]` tokens. To avoid this, extract and replace mention tokens with placeholders *before* Markdown parsing, then restore them after. Flow:
1. Replace `@[...]` tokens with unique placeholders (e.g., `%%MENTION_0%%`)
2. Run `Str::markdown()` on the placeholder text
3. Replace placeholders with rendered `<a>` mention links

**Rendering logic:**
1. Extract all `@[...]` tokens, store mapping of placeholder → token content
2. Strip title parenthetical from each to get bare names
3. Batch-fetch users: `User::whereIn('name', $names)->get()->keyBy('name')` (current code already batch-fetches; this preserves that pattern)
4. Replace placeholders with `<a class="mention" href="/users/{id}">@{name}</a>`
5. Unmatched tokens render as plain text: `@Name` (no link, no brackets)

### 4. SlashCommandService (app/Services/SlashCommandService.php)

**extractMentions():** Same regex update: `/@\[([^\]]+)\]/`. Must also strip title parenthetical before returning names, same as `parseMentions()`.

**`/assign` command:** Currently does `ltrim($args, '@')` then `findByName()`. Update to: parse `@[Name (Title)]` from args, strip title, pass bare name to `findByName()`. If no bracket format detected, strip leading `@` (preserving current `ltrim` behavior) and treat the whole arg as a name (handles `/assign @John Smith` or `/assign John Smith` typed manually).

**`/action` command:** Uses `extractMentions()` output — will work correctly once `extractMentions()` strips titles.

### 5. Controller Data (app/Http/Controllers/TicketsController.php)

**Current:** `$allUsers` is `[id => name]` key-value pairs.

**New:** `$allUsers` becomes a collection of objects: `[{id, name, title}, ...]`

Update `show()` and any other method that passes `$allUsers` to views.

### 6. Backward Compatibility

Old-format mentions (`@username`) in existing notes will no longer render as clickable links — they will display as plain `@username` text. This is a known regression.

**Why this is acceptable:**
- Mention records (in the `mentions` table) are already stored with `user_id` — notifications were already sent
- The functional impact is cosmetic: old mention links lose their styling and click target
- Adding a legacy fallback regex would add complexity for a transitional benefit

**If this is not acceptable,** add a read-only fallback in `replaceMentions()` that also matches `/@([\w.\-]+)/` and renders old-format mentions as links (but new mentions always use bracket format).

## What Doesn't Change

- **Mention model/migration** — still stores `note_id` + `user_id`
- **MentionNotification** — dispatched the same way
- **Activity center** — reads from notification data, not markdown
- **MentionService::createMentions()** — still receives user IDs, works the same
- **NotesController** — calls MentionService but doesn't touch regex or format directly; no changes needed

## Testing

- MentionService: parse `@[John Smith (Dev)]`, `@[Jane]`, empty brackets, nested parens in title
- MarkdownService: render tokens to links, handle unmatched names, batch user fetch (no N+1), verify CommonMark doesn't mangle tokens (placeholder roundtrip)
- SlashCommandService: extract mentions from command text; `/assign @[Name (Title)]` resolves correctly
- Feature test: submit a note with `@[Name (Title)]`, verify mention record created and notification dispatched
- JS: manual testing of autocomplete trigger, filter, keyboard nav, selection, dismissal
