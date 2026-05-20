# Projects, Milestones, and Releases

## Projects

Projects group tickets by team or area of work. Each ticket belongs to one project.

**Navigate to:** Projects (top navigation)

### Create a project

1. Navigate to **Projects → New Project**.
2. Enter a **Name**.
3. Click **Save**.

### Edit a project

1. Navigate to **Projects**.
2. Click the project name.
3. Click **Edit**.

### Project view

The project page shows:
- Progress bar: closed tickets / total tickets
- Ticket list with filters: milestone, status, type, assignee, importance
- Active ticket count and total ticket count

---

## Milestones

Milestones represent sprints or time-boxed work periods. Tickets are assigned to a milestone.

**Navigate to:** Milestones (top navigation)

### Create a milestone

1. Navigate to **Milestones → New Milestone**.
2. Fill in:
   - **Name** — e.g. `Sprint 14`
   - **Start Date** — sprint start (used for burndown chart)
   - **Due Date** — sprint end (used for burndown chart)
   - **Owner** — accountable team member
   - **Scrum Master** — optional
3. Click **Save**.

### Milestone view

The milestone page shows:
- Ticket list grouped by status
- Progress bar: closed / total tickets
- Watcher toggle (subscribe to milestone updates)

### Sprint report

**Navigate to:** Milestones → (select milestone) → **Report**

The report shows:
- Ticket counts: total, completed, open
- Story point totals: total, completed, remaining
- Completion percentage
- Status breakdown (chart)
- Type breakdown (chart)
- Team hours by user
- Per-ticket detail: subject, status, type, assignee, story points, logged hours
- **Burndown chart** — ideal vs actual story point burndown over the sprint. Requires both Start Date and Due Date to be set on the milestone.

### Print view

**Navigate to:** Milestones → (select milestone) → **Print**

Renders a printable ticket list grouped by project and type.

### Watching a milestone

Click **Watch** on a milestone page to subscribe to updates. Click again to unwatch.

---

## Releases

Releases track deployed versions. Tickets can be associated with a release to document what shipped.

**Navigate to:** Releases (top navigation)

### Create a release

1. Navigate to **Releases → New Release**.
2. Fill in:
   - **Title** — version or release name (e.g. `v2.1.0`)
   - **Body** — release notes (Markdown supported)
   - **Started** — optional release start date
   - **Completed** — optional release date
3. Click **Save**.

### Edit a release

1. Navigate to **Releases**.
2. Click the release title.
3. Click **Edit**.

### Release view

Shows tickets grouped by project and type. Each ticket displays status, assignee, and type icon.

### Associating tickets with a release

Tickets are associated with a release from the ticket detail page. Open a ticket and use the **Release** field to select a release.

---

## See also

- [CSV Import](csv-import.md) — bulk importing tickets into a milestone
- [Users](users.md) — setting up project owners and assignees
