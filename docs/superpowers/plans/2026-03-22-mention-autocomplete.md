# @Mention Autocomplete Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace broken `@username` mentions with `@[Name (Title)]` bracket tokens backed by a typeahead autocomplete dropdown.

**Architecture:** Change the mention format from `@word` to `@[Name (Title)]` across three services (MentionService, MarkdownService, SlashCommandService), update the controller to pass user titles, rewrite the Blade dropdown to be JS-driven, and wire up keyboard-navigable autocomplete in the textarea.

**Tech Stack:** Laravel 12 (PHP), Bootstrap 5.3, vanilla JS (no Alpine for this component), PHPUnit with `#[Test]` attributes and `SeedsDatabase` trait.

---

### Task 1: Update MentionService::parseMentions() — New Bracket Regex

**Files:**
- Modify: `app/Services/MentionService.php:11-18`
- Test: `tests/Unit/Services/MentionServiceTest.php`

- [ ] **Step 1: Write failing tests for the new bracket format**

Add these tests to `tests/Unit/Services/MentionServiceTest.php`:

```php
#[Test]
public function it_parses_bracket_mention_with_title(): void
{
    $mentions = $this->service->parseMentions('Hey @[John Smith (Developer)] check this');

    $this->assertSame(['John Smith'], $mentions);
}

#[Test]
public function it_parses_bracket_mention_without_title(): void
{
    $mentions = $this->service->parseMentions('Ask @[Jane Doe] about this');

    $this->assertSame(['Jane Doe'], $mentions);
}

#[Test]
public function it_parses_multiple_bracket_mentions_and_deduplicates(): void
{
    $mentions = $this->service->parseMentions(
        '@[Alice Jones (PM)] and @[Bob Lee (Dev)] — also loop in @[Alice Jones (PM)]'
    );

    $this->assertSame(['Alice Jones', 'Bob Lee'], $mentions);
}

#[Test]
public function it_ignores_old_format_mentions(): void
{
    $mentions = $this->service->parseMentions('Old style @john is ignored');

    $this->assertSame([], $mentions);
}

#[Test]
public function it_ignores_empty_brackets(): void
{
    $mentions = $this->service->parseMentions('@[] should not match');

    $this->assertSame([], $mentions);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=MentionServiceTest`
Expected: 5 new tests fail (old regex doesn't match bracket format)

- [ ] **Step 3: Implement new parseMentions()**

Replace `parseMentions()` in `app/Services/MentionService.php:11-18`:

```php
public function parseMentions(string $markdown): array
{
    preg_match_all('/@\[([^\]]+)\]/u', $markdown, $matches);

    if (empty($matches[1])) {
        return [];
    }

    return array_values(array_unique(array_map(
        fn (string $token) => preg_replace('/\s*\([^)]*\)$/', '', trim($token)),
        $matches[1]
    )));
}
```

- [ ] **Step 4: Update the existing test for the new format**

The existing test `it_parses_unique_usernames_from_markdown` uses old `@alice` format. Update it:

```php
#[Test]
public function it_parses_unique_usernames_from_markdown(): void
{
    $mentions = $this->service->parseMentions(
        "Please sync with @[Alice (PM)] and @[Bob (Dev)].\n".
        "Loop @[Alice (PM)] in again.\n".
        "Ignore support@example.com."
    );

    $this->assertSame(['Alice', 'Bob'], $mentions);
}
```

- [ ] **Step 5: Run all MentionService tests**

Run: `php artisan test --filter=MentionServiceTest`
Expected: All 7 tests pass

- [ ] **Step 6: Commit**

```bash
git add app/Services/MentionService.php tests/Unit/Services/MentionServiceTest.php
git commit -m "Update MentionService to parse @[Name (Title)] bracket format"
```

---

### Task 2: Update MarkdownService::replaceMentions() — Placeholder Strategy

**Files:**
- Modify: `app/Services/MarkdownService.php:10-31` (parse method) and `:33-54` (replaceMentions method)
- Test: `tests/Unit/MarkdownServiceTest.php`

- [ ] **Step 1: Write failing tests for bracket mention rendering**

Add to `tests/Unit/MarkdownServiceTest.php`:

```php
#[Test]
public function it_converts_bracket_mentions_to_links(): void
{
    $user = User::factory()->create(['name' => 'John Smith']);
    $input = 'Hey @[John Smith (Developer)] check this';

    $output = $this->service->parse($input);

    $this->assertStringContainsString(
        '<a class="mention" href="/users/' . $user->id . '">@John Smith</a>',
        $output
    );
    $this->assertStringNotContainsString('Developer', $output);
    $this->assertStringNotContainsString('[', $output);
}

#[Test]
public function it_renders_bracket_mention_without_title(): void
{
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $input = 'Ask @[Jane Doe] about this';

    $output = $this->service->parse($input);

    $this->assertStringContainsString(
        '<a class="mention" href="/users/' . $user->id . '">@Jane Doe</a>',
        $output
    );
}

#[Test]
public function it_renders_unmatched_bracket_mention_as_plain_text(): void
{
    $input = 'Hey @[Nobody Here (Ghost)] check this';

    $output = $this->service->parse($input);

    $this->assertStringContainsString('@Nobody Here', $output);
    $this->assertStringNotContainsString('<a', $output);
    $this->assertStringNotContainsString('[', $output);
}

#[Test]
public function it_does_not_mangle_bracket_mentions_through_markdown_parser(): void
{
    $user = User::factory()->create(['name' => 'Alice Jones']);
    $input = "**Bold** and @[Alice Jones (PM)] in same paragraph";

    $output = $this->service->parse($input);

    $this->assertStringContainsString('<strong>Bold</strong>', $output);
    $this->assertStringContainsString(
        '<a class="mention" href="/users/' . $user->id . '">@Alice Jones</a>',
        $output
    );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=MarkdownServiceTest`
Expected: 4 new tests fail

- [ ] **Step 3: Implement placeholder strategy in parse() and updated replaceMentions()**

Replace `parse()` and `replaceMentions()` in `app/Services/MarkdownService.php`:

```php
public function parse(?string $text): string
{
    if (empty($text)) {
        return '';
    }

    $text = $this->wrapStackTrace($text);

    // Extract @[...] mention tokens before Markdown parsing (brackets are link syntax)
    $mentionMap = [];
    $text = preg_replace_callback('/@\[([^\]]+)\]/u', function ($match) use (&$mentionMap) {
        $placeholder = '%%MENTION_' . count($mentionMap) . '%%';
        $mentionMap[$placeholder] = $match[1];
        return $placeholder;
    }, $text);

    $lines = explode("\n", $text);
    foreach ($lines as &$line) {
        if (str_starts_with(trim($line), '/')) {
            $line = '<code class="slash-command">'.e(trim($line)).'</code>';
        }
    }
    $text = implode("\n", $lines);

    $html = Str::markdown($text);

    $html = $this->replaceMentions($html, $mentionMap);

    return $this->decorateChecklistItems($html);
}

private function replaceMentions(string $html, array $mentionMap): string
{
    if (empty($mentionMap)) {
        return $html;
    }

    // Strip title parenthetical to get bare names
    $namesByPlaceholder = [];
    foreach ($mentionMap as $placeholder => $token) {
        $namesByPlaceholder[$placeholder] = preg_replace('/\s*\([^)]*\)$/', '', trim($token));
    }

    $names = array_unique(array_values($namesByPlaceholder));
    $users = User::whereIn('name', $names)->get()->keyBy('name');

    foreach ($namesByPlaceholder as $placeholder => $name) {
        $user = $users[$name] ?? null;
        if ($user) {
            $replacement = '<a class="mention" href="/users/'.$user->id.'">@'.e($name).'</a>';
        } else {
            $replacement = '@'.e($name);
        }
        $html = str_replace($placeholder, $replacement, $html);
    }

    return $html;
}
```

- [ ] **Step 4: Update existing mention test for new format**

Update `it_converts_mentions_to_links` in `tests/Unit/MarkdownServiceTest.php`:

```php
#[Test]
public function it_converts_mentions_to_links()
{
    $user = User::factory()->create(['name' => 'JohnDoe']);
    $input = "Hello @[JohnDoe]!";

    $output = $this->service->parse($input);

    $this->assertStringContainsString(
        '<a class="mention" href="/users/' . $user->id . '">@JohnDoe</a>',
        $output
    );
}
```

- [ ] **Step 5: Run all MarkdownService tests**

Run: `php artisan test --filter=MarkdownServiceTest`
Expected: All 10 tests pass (6 existing + 4 new)

- [ ] **Step 6: Commit**

```bash
git add app/Services/MarkdownService.php tests/Unit/MarkdownServiceTest.php
git commit -m "Update MarkdownService to render @[Name (Title)] mentions with placeholder strategy"
```

---

### Task 3: Update SlashCommandService — extractMentions() and /assign

**Files:**
- Modify: `app/Services/SlashCommandService.php:102-111` (assign case) and `:220-224` (extractMentions)
- Test: `tests/Unit/SlashCommandServiceTest.php`

- [ ] **Step 1: Write failing tests for bracket format in slash commands**

Add to `tests/Unit/SlashCommandServiceTest.php`:

```php
#[Test]
public function it_can_assign_user_via_bracket_mention(): void
{
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create(['name' => 'John Smith']);

    $result = $this->service->handle($ticket, '/assign @[John Smith (Developer)]');

    $this->assertEquals($user->id, $ticket->fresh()->user_id2);
    $this->assertSame([
        ['action' => 'assigned', 'to' => 'John Smith'],
    ], $result['actions']);
}

#[Test]
public function it_extracts_bracket_mentions_in_action_commands(): void
{
    $ticket = Ticket::factory()->create();
    $assignee = User::factory()->create(['name' => 'Sarah Lee']);

    $result = $this->service->handle($ticket, '/action Verify fix @[Sarah Lee (QA)]');

    $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
    $this->assertSame('action', $result['note_type']);
}

#[Test]
public function it_falls_back_to_bare_name_for_assign(): void
{
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create(['name' => 'John Smith']);

    $result = $this->service->handle($ticket, '/assign John Smith');

    $this->assertEquals($user->id, $ticket->fresh()->user_id2);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=SlashCommandServiceTest`
Expected: 3 new tests fail

- [ ] **Step 3: Update extractMentions() and assign case**

In `app/Services/SlashCommandService.php`, replace `extractMentions()` (lines 220-224):

```php
protected function extractMentions(string $text): array
{
    preg_match_all('/@\[([^\]]+)\]/u', $text, $matches);

    return array_values(array_unique(array_map(
        fn (string $token) => preg_replace('/\s*\([^)]*\)$/', '', trim($token)),
        $matches[1] ?? []
    )));
}
```

Replace the `assign` case (lines 102-111):

```php
case 'assign':
    $name = $this->parseMentionOrBareName($args);
    $user = $this->findByName(User::class, $name);
    if ($user) {
        $ticket->user_id2 = $user->id;
        $ticket->save();
        $result['changes'][] = "Assigned to {$user->name}";
        $result['actions'][] = ['action' => 'assigned', 'to' => $user->name];
    }
    break;
```

Add the helper method after `findByName()`:

```php
protected function parseMentionOrBareName(string $args): string
{
    // Try bracket format first: @[Name (Title)]
    if (preg_match('/@\[([^\]]+)\]/u', $args, $match)) {
        return preg_replace('/\s*\([^)]*\)$/', '', trim($match[1]));
    }

    // Fall back to bare name (strip leading @)
    return ltrim(trim($args), '@');
}
```

- [ ] **Step 4: Update existing assign test for bracket format**

Update `it_can_assign_user_via_slash_command` in the test file:

```php
#[Test]
public function it_can_assign_user_via_slash_command()
{
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create(['name' => 'JohnDoe']);

    $result = $this->service->handle($ticket, '/assign @[JohnDoe]');

    $this->assertEquals($user->id, $ticket->fresh()->user_id2);
    $this->assertSame([
        ['action' => 'assigned', 'to' => 'JohnDoe'],
    ], $result['actions']);
}
```

- [ ] **Step 5: Update multi-command test**

Update `it_supports_multi_command_submissions_and_collects_body_text`:

```php
#[Test]
public function it_supports_multi_command_submissions_and_collects_body_text()
{
    $ticket = Ticket::factory()->create();
    $assignee = User::factory()->create(['name' => 'john']);
    $status = Status::factory()->create(['name' => 'Testing']);

    $result = $this->service->handle($ticket, "/assign @[john]\n/status Testing\n/action Verify fix on staging @[john]\nFound the root cause in the payment handler.");

    $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
    $this->assertEquals($status->id, $ticket->fresh()->status_id);
    $this->assertSame('action', $result['note_type']);
    $this->assertStringContainsString('Verify fix on staging @[john]', $result['body']);
    $this->assertStringContainsString('Found the root cause in the payment handler.', $result['body']);
}
```

- [ ] **Step 6: Update action assignee and auto-assign tests**

Update `it_requires_exactly_one_assignee_for_action_commands`:

```php
#[Test]
public function it_requires_exactly_one_assignee_for_action_commands()
{
    $ticket = Ticket::factory()->create();

    $missing = $this->service->handle($ticket, '/action Verify fix');
    $multiple = $this->service->handle($ticket, '/action Verify fix @[john] @[sarah]');

    $this->assertContains('Actions require exactly one @assignee', $missing['changes']);
    $this->assertContains('Actions require exactly one @assignee', $multiple['changes']);
}
```

Update `it_auto_assigns_unassigned_tickets_to_the_action_assignee`:

```php
#[Test]
public function it_auto_assigns_unassigned_tickets_to_the_action_assignee()
{
    $ticket = Ticket::factory()->create(['user_id2' => 999999]);
    $assignee = User::factory()->create(['name' => 'sarah']);

    $result = $this->service->handle($ticket, '/action Verify fix @[sarah]');

    $this->assertEquals($assignee->id, $ticket->fresh()->user_id2);
    $this->assertContains(['action' => 'assigned', 'to' => 'sarah'], $result['actions']);
}
```

- [ ] **Step 7: Run all SlashCommandService tests**

Run: `php artisan test --filter=SlashCommandServiceTest`
Expected: All tests pass

- [ ] **Step 8: Commit**

```bash
git add app/Services/SlashCommandService.php tests/Unit/SlashCommandServiceTest.php
git commit -m "Update SlashCommandService for @[Name (Title)] bracket mentions"
```

---

### Task 4: Update Controller and Blade Template — User Data with Titles

**Files:**
- Modify: `app/Http/Controllers/TicketsController.php:218`
- Modify: `resources/views/tickets/show.blade.php:85` and `:126-133`
- Test: `tests/Feature/Controllers/TicketsControllerTest.php` (existing)

- [ ] **Step 1: Write failing test for allUsers data shape**

Add to the existing feature test file `tests/Feature/Controllers/TicketsControllerTest.php`:

```php
#[Test]
public function show_passes_all_users_with_titles(): void
{
    $user = User::factory()->create(['name' => 'Alice', 'title' => 'PM']);
    $ticket = Ticket::factory()->create(['user_id' => $user->id, 'user_id2' => $user->id]);
    $this->actingAs($user);

    $response = $this->get("/tickets/{$ticket->id}");

    $response->assertOk();
    $response->assertViewHas('allUsers');
    $allUsers = $response->viewData('allUsers');
    $found = collect($allUsers)->firstWhere('id', $user->id);
    $this->assertNotNull($found);
    $this->assertEquals('Alice', $found['name']);
    $this->assertEquals('PM', $found['title']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=show_passes_all_users_with_titles`
Expected: FAIL — current `$allUsers` is a `pluck('name', 'id')` collection, not objects with titles

- [ ] **Step 3: Update controller to pass users with titles**

In `app/Http/Controllers/TicketsController.php`, replace line 218:

```php
$allUsers = User::orderBy('name')->get(['id', 'name', 'title'])->map(fn ($u) => [
    'id' => $u->id,
    'name' => $u->name,
    'title' => $u->title,
])->values()->toArray();
```

- [ ] **Step 4: Update Blade template — data attribute and dropdown**

In `resources/views/tickets/show.blade.php`, line 85 stays the same (already uses `@json($allUsers)`).

Replace lines 126-133 (the `@Mention Autocomplete` section) with a JS-driven empty container:

```blade
{{-- @Mention Autocomplete (populated by JS from data-users) --}}
<div class="mention-autocomplete dropdown-menu position-absolute d-none"></div>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=show_passes_all_users_with_titles`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/TicketsController.php resources/views/tickets/show.blade.php
git commit -m "Pass user titles in allUsers and convert mention dropdown to JS-driven"
```

---

### Task 5: Wire Up JS Autocomplete

**Files:**
- Modify: `resources/views/tickets/show.blade.php` (script section, after line ~618)

- [ ] **Step 1: Add the autocomplete JS**

Add this script block after the existing signal nudge handler (~line 618) in `resources/views/tickets/show.blade.php`:

```javascript
// --- @Mention Autocomplete ---
(function() {
    const textarea = document.getElementById('note-textarea');
    const composer = textarea?.closest('.markdown-composer');
    if (!textarea || !composer) return;

    const dropdown = composer.querySelector('.mention-autocomplete');
    const users = JSON.parse(composer.dataset.users || '[]');
    let mentionStart = -1;
    let activeIndex = -1;

    function buildDropdown(filter) {
        const lower = filter.toLowerCase();
        const filtered = lower === ''
            ? users
            : users.filter(u => u.name.toLowerCase().includes(lower));

        dropdown.innerHTML = '';
        activeIndex = -1;

        if (filtered.length === 0) {
            hide();
            return;
        }

        filtered.forEach((u, i) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dropdown-item';
            btn.dataset.userId = u.id;
            btn.dataset.userName = u.name;
            btn.dataset.userTitle = u.title || '';
            btn.textContent = u.title ? `${u.name} (${u.title})` : u.name;
            btn.addEventListener('mousedown', function(e) {
                e.preventDefault();
                selectUser(u);
            });
            dropdown.appendChild(btn);
        });

        dropdown.classList.remove('d-none');
        dropdown.classList.add('show');
        setActive(0);
    }

    function selectUser(u) {
        const token = u.title ? `@[${u.name} (${u.title})]` : `@[${u.name}]`;
        const before = textarea.value.substring(0, mentionStart);
        const after = textarea.value.substring(textarea.selectionStart);
        textarea.value = before + token + ' ' + after;
        const cursorPos = before.length + token.length + 1;
        textarea.setSelectionRange(cursorPos, cursorPos);
        hide();
        textarea.focus();
    }

    function setActive(index) {
        const items = dropdown.querySelectorAll('.dropdown-item');
        items.forEach(i => i.classList.remove('active'));
        if (index >= 0 && index < items.length) {
            activeIndex = index;
            items[index].classList.add('active');
            items[index].scrollIntoView({ block: 'nearest' });
        }
    }

    function hide() {
        dropdown.classList.add('d-none');
        dropdown.classList.remove('show');
        mentionStart = -1;
        activeIndex = -1;
    }

    textarea.addEventListener('input', function() {
        const pos = this.selectionStart;
        const text = this.value.substring(0, pos);

        // Find the last unmatched @ (not inside brackets)
        const lastAt = text.lastIndexOf('@');
        if (lastAt === -1 || (lastAt > 0 && /\w/.test(text[lastAt - 1]))) {
            hide();
            return;
        }

        // Check we're not already inside a completed @[...] token
        const afterAt = text.substring(lastAt);
        if (afterAt.includes(']')) {
            hide();
            return;
        }

        mentionStart = lastAt;
        const filter = text.substring(lastAt + 1);
        buildDropdown(filter);
    });

    textarea.addEventListener('keydown', function(e) {
        if (mentionStart === -1) return;

        const items = dropdown.querySelectorAll('.dropdown-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(Math.min(activeIndex + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            const item = items[activeIndex];
            selectUser({
                id: item.dataset.userId,
                name: item.dataset.userName,
                title: item.dataset.userTitle
            });
        } else if (e.key === 'Escape') {
            e.preventDefault();
            hide();
        }
    });

    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== textarea) {
            hide();
        }
    });
})();
```

- [ ] **Step 2: Manual test the autocomplete**

Open a ticket show page in the browser. Type `@` in the note textarea:
- Dropdown should appear with all users (name + title)
- Type a few characters — list filters
- Arrow keys navigate, Enter selects
- Selection inserts `@[Name (Title)]` token
- Escape dismisses
- Clicking outside dismisses

- [ ] **Step 3: Commit**

```bash
git add resources/views/tickets/show.blade.php
git commit -m "Add JS autocomplete for @mention with keyboard navigation"
```

---

### Task 6: Fix NotesController Mention Bug and promote() Regex

**Files:**
- Modify: `app/Http/Controllers/NotesController.php:66, 75-76, 177`

**Context:** Two issues in NotesController:
1. Line 177: `parseMentions()` result (display names) passed directly to `createMentions()` which expects integer user IDs. Pre-existing bug.
2. Lines 66, 75: `promote()` method uses old regex `/@([\w.\-]+)/` to detect action assignees. Must update to bracket format.

Note: The API controller (`Api/TicketController.php`) already correctly resolves names to IDs via `User::whereIn('name', ...)` — no changes needed there.

- [ ] **Step 1: Write failing test for note edit mentions**

Add to the appropriate feature test file:

```php
#[Test]
public function editing_a_note_creates_mentions_for_bracket_format(): void
{
    $author = User::factory()->create();
    $mentioned = User::factory()->create(['name' => 'Alice Jones', 'title' => 'PM']);
    $ticket = Ticket::factory()->create(['user_id' => $author->id, 'user_id2' => $author->id]);
    $note = Note::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $author->id]);

    $this->actingAs($author);

    $response = $this->putJson("/notes/{$note->id}", [
        'body' => 'Updated: @[Alice Jones (PM)] check this',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('mentions', [
        'note_id' => $note->id,
        'user_id' => $mentioned->id,
    ]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=editing_a_note_creates_mentions_for_bracket_format`
Expected: FAIL — names passed as user IDs cause `createMentions()` to fail silently

- [ ] **Step 3: Fix NotesController mention resolution**

In `app/Http/Controllers/NotesController.php`, replace line 177:

```php
$usernames = $mentions->parseMentions($validated['body']);
$userIds = User::whereIn('name', $usernames)->pluck('id')->all();
$mentions->createMentions($note, $userIds);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=editing_a_note_creates_mentions_for_bracket_format`
Expected: PASS

- [ ] **Step 5: Update promote() regex for bracket format**

In `app/Http/Controllers/NotesController.php`, replace line 66:

```php
if ($type === 'action' && ! preg_match('/@\[([^\]]+)\]/u', $body) && ! request('assignee')) {
```

Replace line 75-76:

```php
if (request('type') === 'action' && request('assignee') && ! preg_match('/@\[([^\]]+)\]/u', $note->body)) {
    $assigneeName = ltrim((string) request('assignee'), '@');
    $note->body = rtrim($note->body).' @['.$assigneeName.']';
}
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=NotesController`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/NotesController.php
git commit -m "Fix NotesController mention resolution and update promote() to bracket format"
```

---

### Task 7: Run Full Test Suite and Fix Regressions

**Files:**
- All modified files from Tasks 1-6

- [ ] **Step 1: Run all mention-related tests**

Run: `php artisan test --filter=MentionServiceTest && php artisan test --filter=MarkdownServiceTest && php artisan test --filter=SlashCommandServiceTest`
Expected: All pass

- [ ] **Step 2: Run broader test suites to catch regressions**

Run: `php artisan test --filter=TicketsControllerTest && php artisan test --filter=NotesControllerTest`
Expected: All pass. If any test references old `@username` format in assertions, update to `@[Name]` format.

- [ ] **Step 3: Fix any regressions found**

Check for tests that assert old `@username` mention format and update them.

- [ ] **Step 4: Final commit if any fixes needed**

```bash
git add -A
git commit -m "Fix test regressions from mention format migration"
```
