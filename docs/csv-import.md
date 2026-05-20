# CSV Import

CSV import lets you bulk-create tickets from a spreadsheet. Each row becomes one ticket.

## Navigate to the import page

**Tickets → Import** (top navigation bar).

## File requirements

- Format: CSV (`.csv`)
- Encoding: UTF-8
- Max size: 10 MB
- Optional header row: check **File has a header row** if your CSV includes column names in row 1

## Column format

Every row must have exactly 7 columns in this order:

| Column | Field | Required | Notes |
|--------|-------|----------|-------|
| 1 | Type | Yes | Must match an existing type name exactly (e.g. `Bug`, `Feature`) |
| 2 | Subject | Yes | Ticket title — cannot be blank |
| 3 | Description | Yes | Body text — can be empty string but column must be present |
| 4 | Importance | Yes | Must match an existing importance level name (e.g. `High`, `Low`) |
| 5 | Status | Yes | Must match an existing status name (e.g. `Open`, `In Progress`) |
| 6 | Project | Yes | Must match an existing project name exactly |
| 7 | Assignee | Yes | Must match an existing user's name exactly |

All lookup values (Type, Importance, Status, Project, Assignee) must already exist in the system. Use **Admin → Lookups** to see available values, or call `GET /api/v1/lookups`.

## Milestone

Select a milestone from the dropdown before uploading. All imported tickets are assigned to that milestone.

## Example CSV

```csv
Type,Subject,Description,Importance,Status,Project,Assignee
Bug,Login page 500 error,Reproducible on Chrome only,High,Open,Backend,Jane Smith
Feature,Dark mode toggle,User preference stored in profile,Medium,Backlog,Frontend,John Doe
```

## Error handling

If any row fails validation, the entire import is rolled back — no tickets are created. The error message identifies the row number and column that caused the failure. Fix the CSV and re-upload.

Common errors:

| Error | Cause |
|-------|-------|
| `Row 3: Subject (column 2) is required.` | Column 2 is blank |
| `Status Closed does not exist.` | Lookup value doesn't match any record |
| `CSV row must have at least 7 columns.` | Row has fewer than 7 comma-separated values |
| `CSV file must be UTF-8 encoded.` | File saved in a non-UTF-8 encoding (e.g. Windows-1252) |

## After import

On success, you are redirected to the milestone page where the new tickets appear.

---

## See also

- [Projects](projects.md) — managing milestones tickets are imported into
- [API](api.md) — `POST /tickets` for single-ticket creation via API
