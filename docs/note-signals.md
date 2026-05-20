# Note Signals

Notes on a ticket are more than comments — they carry structured signals that drive the ticket's [Pulse](pulse.md), decision log, and action tracking. Choosing the right signal type keeps the ticket's history meaningful and machine-readable.

## Signal types

| Type | Slash command | Purpose |
|------|--------------|---------|
| `message` | *(default, no command)* | General discussion, updates, context |
| `decision` | `/decision` | Records a resolved choice — immutable once saved |
| `blocker` | `/blocker` | Flags an impediment — drives `BLOCKED` Pulse state |
| `action` | `/action @user description` | Assigns a next step — drives `ON TRACK` Pulse state |
| `update` | `/update` | Marks the note as a status update |
| `changelog` | *(auto-generated)* | Created automatically when ticket fields change — not user-authored |

## When to use each type

**message** — Default. Use for discussion, questions, and context that doesn't fit another category.

**decision** — Use when a meaningful choice has been made that the team should be able to look back on. Decisions cannot be edited after saving. To supersede a decision, create a new `/decision` — the old one is marked as superseded in the history.

**blocker** — Use when something external is preventing progress. This immediately sets the ticket Pulse to `BLOCKED` and surfaces the blocker text to anyone viewing the ticket. Resolve the blocker note when the impediment clears.

**action** — Use to assign a concrete next step. Requires `@mention` of the responsible person. Keeps the Pulse at `ON TRACK` as long as the action is unresolved and activity is recent. Resolve the action when the step is complete.

**update** — Use for a formal status update that's more than a comment but less than a decision. Useful for end-of-day summaries or stakeholder updates.

## Slash commands

Type a slash command at the start of any line in the note body. Commands can be combined — put each on its own line.

### Field commands

| Command | Syntax | Effect |
|---------|--------|--------|
| `/status` | `/status Open` | Change ticket status by name |
| `/assign` | `/assign @jane` or `/assign jane@example.com` | Reassign ticket |
| `/close` | `/close` | Close the ticket |
| `/reopen` | `/reopen` | Reopen a closed ticket |
| `/estimate` | `/estimate 8` | Set estimate in hours |
| `/hours` | `/hours 2.5` | Log hours against this ticket |
| `/priority` | `/priority High` | Set importance/priority by name |
| `/milestone` | `/milestone Sprint 15` | Move ticket to a milestone by name |
| `/pin` | `/pin` | Pin this note to the top of the thread |

### Signal commands

| Command | Syntax | Effect |
|---------|--------|--------|
| `/decision` | `/decision We will use Postgres` | Mark note as a decision |
| `/blocker` | `/blocker Waiting on legal approval` | Mark note as a blocker |
| `/action` | `/action @jane Deploy to staging` | Create an action item assigned to @jane |
| `/update` | `/update Sprint is on track` | Mark note as a status update |

### Combining commands

```
/status In Progress
/hours 3
/action @john Write unit tests for the new endpoint
```

This single note logs 3 hours, changes the status to "In Progress", and creates an action item for John.

## Resolving notes

Blocker and action notes should be resolved when their purpose is complete.

1. Open the note.
2. Click **Resolve**.
3. Enter a resolution message (required).
4. The note is marked resolved and a reply is created with the message.

Resolving a blocker immediately recalculates the Pulse — the ticket exits `BLOCKED` state.

## Pinning notes

Click **Pin** on any note to keep it visible at the top of the thread. Useful for decisions, active blockers, or key context. Click again to unpin.

## Reactions

Two reactions are supported: 👍 (`thumbsup`) and 👀 (`eyes`). Click to toggle. Reactions are visible to all users on the ticket.

Via API: `POST /api/v1/tickets/{id}/notes/{noteId}/react` with `{"emoji": "thumbsup"}`.

## Threaded replies

Click **Reply** on any top-level note to add a reply. Replies cannot be nested (cannot reply to a reply). An unresolved note with replies counts as an "open thread" in the Pulse.

## @mentions

Type `@` in the note body to trigger autocomplete. Select a user to insert `@[Name]`. Mentioned users receive a separate notification regardless of whether they are watching the ticket.

## Smart paste

Paste content into the note editor and it auto-formats:
- **Stack traces** — wrapped in a code block
- **JSON** — wrapped in a code block with JSON syntax hint
- **URLs** — converted to Markdown links

---

## See also

- [Pulse](pulse.md) — how signal types drive Pulse state
- [Notifications](notifications.md) — how @mentions and watcher notifications work
- [Agile Workflow](agile-workflow.md) — using signals during sprints
- [API](api.md) — `POST /tickets/{id}/note`, resolve, react endpoints
