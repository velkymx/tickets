# Agile Workflow

Tickets is designed around a three-level hierarchy that maps directly to agile practice: **Projects** contain **Milestones** (sprints), which contain **Tickets** (work items).

## The hierarchy

```
Project → Milestone (Sprint) → Ticket
```

- **Project** — a team, product area, or initiative. Tickets belong to one project.
- **Milestone** — a time-boxed sprint or delivery goal. Has a start date, due date, owner, and scrum master.
- **Ticket** — a single unit of work. Has type, status, importance, story points, estimate, assignee, and due date.

## Sprint setup

### 1. Create the project (if needed)

Navigate to **Projects → New Project**. Enter a name and save.

### 2. Create the milestone (sprint)

Navigate to **Milestones → New Milestone**. Fill in:

| Field | Notes |
|-------|-------|
| Name | e.g. `Sprint 14` or `Q2 Launch` |
| Start Date | Sprint start — required for burndown chart |
| Due Date | Sprint end — required for burndown chart |
| Owner | Accountable team member |
| Scrum Master | Optional |

### 3. Add tickets to the sprint

**Option A — Create individually:** Navigate to **Tickets → New Ticket**. Select the milestone in the form.

**Option B — Bulk import:** Use [CSV Import](csv-import.md) to create many tickets at once into a milestone.

**Option C — Via API:** `POST /api/v1/tickets` with `milestone_id` set.

### 4. Set story points

Open each ticket and set **Story Points**. These power the burndown chart and sprint report velocity calculations.

## Running the sprint

### Kanban board

Navigate to the milestone page. Tickets are displayed in columns by status. Drag a ticket to a new column to change its status instantly.

### Updating a ticket

Open a ticket and add a note. Use slash commands to update fields without leaving the note box:

| Command | Effect |
|---------|--------|
| `/status In Progress` | Move ticket to a status by name |
| `/hours 2` | Log 2 hours against this ticket |
| `/estimate 8` | Set estimate to 8 hours |
| `/close` | Close the ticket |
| `/assign @jane` | Reassign the ticket |

See [Note Signals](note-signals.md) for the full slash command reference.

### Tracking blockers

When a ticket is blocked, add a `/blocker` note explaining what's blocking it. The ticket's [Pulse](pulse.md) immediately shows `BLOCKED` and surfaces the blocker to anyone viewing the ticket or scanning via API.

Resolve the blocker note when the impediment is cleared — the Pulse recalculates automatically.

### Logging decisions

Use `/decision` notes to record choices made during the sprint. Decisions are immutable once saved and appear in the ticket's decision history.

## Sprint review

Navigate to **Milestones → (select milestone) → Report**.

The sprint report shows:

| Section | What it contains |
|---------|----------------|
| Summary | Total, completed, and open ticket counts |
| Story Points | Total, completed, remaining, and completion % |
| Status breakdown | Ticket count per status |
| Type breakdown | Ticket count per type (bug, feature, etc.) |
| Team hours | Hours logged per team member |
| Ticket detail | Per-ticket: status, assignee, story points, logged hours |
| Burndown chart | Ideal vs actual story point burndown over the sprint |

The burndown chart requires both **Start Date** and **Due Date** to be set on the milestone.

## Closing a sprint

1. Close or move remaining open tickets to the next sprint.
2. Mark finished tickets as closed (use `/close` or drag to a closed-status column).
3. Review the sprint report to capture velocity and team hours.
4. Create the next milestone and repeat.

## Ticket lifecycle

```
Backlog → In Progress → Review → Done (closed)
```

Status names are configurable — the above is the default seeded data. Closed statuses are determined by the `closed_at` timestamp set when a ticket moves to a status marked as closed.

---

## See also

- [Projects, Milestones & Releases](projects.md) — creating and managing the containers
- [Note Signals](note-signals.md) — slash commands, decisions, blockers, actions
- [Pulse](pulse.md) — automated ticket health signal
- [CSV Import](csv-import.md) — bulk ticket creation for sprint planning
