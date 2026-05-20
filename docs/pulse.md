# Ticket Pulse

Every ticket has a **Pulse** — an automated health signal computed from its note activity. The Pulse tells you whether a ticket is on track, at risk, blocked, or idle without reading every note.

## Execution states

| State | Meaning | When it applies |
|-------|---------|----------------|
| `ON TRACK` | Active and progressing | Has an unresolved action note AND activity within the last 48 hours |
| `AT RISK` | May be stalling | No unresolved action, OR last activity was more than 48 hours ago |
| `BLOCKED` | Impediment is recorded | An unresolved blocker note exists |
| `IDLE` | No activity at all | No notes, no action, and ticket is not closed |

States are mutually exclusive and evaluated in priority order: `BLOCKED` > `IDLE` > `AT RISK` > `ON TRACK`.

## Pulse components

The Pulse surfaces more than just a state label. Each pulse includes:

| Component | What it shows |
|-----------|--------------|
| **Execution state** | `ON TRACK`, `AT RISK`, `BLOCKED`, or `IDLE` |
| **Is blocked** | Boolean — true if an active blocker exists |
| **Blocker reason** | Text of the active (unresolved) blocker note |
| **Owner label** | Assignee name, or "Waiting on: @mention" if blocked |
| **Next action** | The most recent unresolved `/action` note |
| **Latest decision** | The most recent `/decision` note (not superseded) |
| **Open threads** | Notes with replies that have not been resolved |
| **Last activity** | Timestamp of the most recent note |
| **Is stale** | True if last activity was more than 48 hours ago |
| **Staleness message** | Human-readable "No updates in X hours" |

## How to view the Pulse

**In the UI:** Open any ticket. The Pulse panel appears on the right side of the ticket detail page, showing state, blocker, next action, and open threads.

**Via API:** `GET /api/v1/tickets/{id}/pulse` returns the full pulse object.

**In the ticket list:** `GET /api/v1/tickets?include=pulse` adds a `pulse_summary` to each item in the list response — useful for triage agents scanning many tickets at once.

## How the state is computed

The Pulse is recomputed from the ticket's notes and cached for one hour. It recalculates automatically when a note is added, edited, or resolved.

**BLOCKED:** One or more `/blocker` notes exist that have not been resolved.

**IDLE:** The ticket has no notes, no unresolved action, and is not closed.

**AT RISK:** Either:
- No unresolved `/action` note exists (no clear next step), or
- The last note is older than 48 hours (activity has gone stale)

**ON TRACK:** Has an unresolved `/action` note AND the last note was within the past 48 hours.

## Moving a ticket out of a bad state

| Current state | How to improve it |
|---------------|------------------|
| `BLOCKED` | Resolve the blocker note (open the note, click **Resolve**, enter a resolution message) |
| `IDLE` | Add any note — even a brief status message moves it to `AT RISK` or better |
| `AT RISK` | Add a `/action @assignee next step` note to define the next step and reset the activity timer |

## Using Pulse for triage (AI agents)

Call `GET /api/v1/tickets?include=pulse` to get pulse state for all assigned tickets. Filter by `execution_state` to find blocked or idle tickets. Use `GET /api/v1/tickets/{id}/pulse` for the full detail including open threads and latest blocker text.

Example: find all BLOCKED tickets and surface them in a standup report by reading `blocker_reason` and `owner_label` from the pulse response.

---

## See also

- [Note Signals](note-signals.md) — creating blocker, action, and decision notes that drive Pulse state
- [Agile Workflow](agile-workflow.md) — using Pulse during sprint execution
- [API](api.md) — `GET /tickets/{id}/pulse` and `?include=pulse` query parameter
