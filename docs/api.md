# REST API

Tickets exposes a REST API at `/api/v1`. All requests (except `/health`) require a Bearer token.

An interactive version of this reference is available at `/api/docs` (Swagger UI).

## Authentication

### Generate a token

1. Log in and navigate to **Profile → Edit Profile** (top-right user menu).
2. Scroll to **API Token**.
3. Click **Generate Token**.
4. Copy the token — it is shown only once.

### Use the token

Include the token in every request:

```
Authorization: Bearer <your-token>
```

### Revoke a token

Navigate to **Profile → Edit Profile → API Token** and click **Revoke Token**. The existing token is immediately invalidated.

---

## Base URL

```
https://your-app.example.com/api/v1
```

---

## Endpoints

### Health Check

```
GET /health
```

No authentication required. Returns `{"status":"ok"}` if the API is reachable.

```bash
curl https://your-app.example.com/api/v1/health
```

---

### Get Lookups

```
GET /lookups
```

Returns all reference data needed to create or update tickets.

```bash
curl -H "Authorization: Bearer <token>" \
  https://your-app.example.com/api/v1/lookups
```

**Response:**

```json
{
  "data": {
    "statuses": [{ "id": 1, "name": "Open" }],
    "types": [{ "id": 1, "name": "Bug" }],
    "importance": [{ "id": 1, "name": "High" }],
    "projects": [{ "id": 1, "name": "Backend" }],
    "milestones": [{ "id": 1, "name": "Milestone 1" }]
  }
}
```

Use the IDs from this response when creating or updating tickets.

---

### List Tickets

```
GET /tickets
```

Returns tickets assigned to the authenticated user, paginated.

**Query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page. Default: 20. Max: 100. |
| `status` | integer | Filter by status ID. |
| `unassigned` | boolean | Set to `1` to list unassigned tickets instead. |
| `include` | string | Set to `pulse` to include pulse summary on each ticket. |

```bash
curl -H "Authorization: Bearer <token>" \
  "https://your-app.example.com/api/v1/tickets?per_page=50&status=1"
```

---

### Get Ticket

```
GET /tickets/{id}
```

Returns full ticket detail: metadata, notes, reactions, attachments, mentions, and pulse summary.

**Query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `notetype` | string | Filter notes by type: `message`, `blocker`, `action`, `decision`, `changelog` |

```bash
curl -H "Authorization: Bearer <token>" \
  https://your-app.example.com/api/v1/tickets/42
```

---

### Create Ticket

```
POST /tickets
```

**Required fields:** `subject`, `type_id`, `importance_id`, `project_id`, `milestone_id`.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Login page returns 500",
    "type_id": 1,
    "importance_id": 2,
    "project_id": 1,
    "milestone_id": 3,
    "description": "Reproducible on Chrome/Mac only.",
    "due_at": "2026-06-01"
  }' \
  https://your-app.example.com/api/v1/tickets
```

**Response (201):**

```json
{
  "message": "Ticket created.",
  "ticket": {
    "id": 123,
    "subject": "Login page returns 500",
    "status": "Open",
    "link": "https://your-app.example.com/tickets/123"
  }
}
```

---

### Add Note / Update Ticket

```
POST /tickets/{id}/note
```

Adds a note, logs hours, changes status, or runs slash commands.

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `body` | string | Note content in Markdown. Supports slash commands (see below). |
| `status_id` | integer | Change ticket status. |
| `hours` | number | Log hours against this ticket. |
| `claim` | boolean | Assign the ticket to yourself. |

**Slash commands** (include in `body`):

| Command | Effect |
|---------|--------|
| `/status Open` | Change status by name |
| `/assign jane@example.com` | Reassign ticket |
| `/close` | Close the ticket |
| `/reopen` | Reopen the ticket |
| `/estimate 8` | Set estimate in hours |
| `/hours 2` | Log 2 hours |
| `/pin` | Pin this note |
| `/decision` | Mark note as a decision |
| `/blocker` | Mark note as a blocker |
| `/action @user Task description` | Create an action item |
| `/update` | Mark as a status update |
| `/milestone Milestone 2` | Move to milestone by name |

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"body": "/close Fixed in commit abc123.", "hours": 1.5}' \
  https://your-app.example.com/api/v1/tickets/42/note
```

---

### Reply to a Note

```
POST /tickets/{id}/notes/{noteId}/reply
```

Adds a reply to a top-level note. Cannot reply to a reply.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"body": "Confirmed, works on my machine too."}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99/reply
```

---

### Edit a Note

```
PUT /tickets/{id}/notes/{noteId}
```

Updates note content. Only the note author can edit. Decision notes cannot be edited.

```bash
curl -X PUT \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"body": "Updated text."}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99
```

---

### Resolve a Note

```
POST /tickets/{id}/notes/{noteId}/resolve
```

Marks a note as resolved. Creates a reply with the resolution message.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"resolution_message": "Fixed in v2.1 hotfix."}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99/resolve
```

---

### React to a Note

```
POST /tickets/{id}/notes/{noteId}/react
```

Toggles a reaction (adds if absent, removes if present). Supported emojis: `thumbsup`, `eyes`.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"emoji": "thumbsup"}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99/react
```

---

### Get Ticket Pulse

```
GET /tickets/{id}/pulse
```

Returns execution state summary: blockers, open threads, next action, latest decision, staleness.

**Execution states:** `ON TRACK`, `AT RISK`, `BLOCKED`, `IDLE`.

```bash
curl -H "Authorization: Bearer <token>" \
  https://your-app.example.com/api/v1/tickets/42/pulse
```

---

## Error Responses

All errors return JSON:

```json
{ "message": "The subject field is required." }
```

| Status | Meaning |
|--------|---------|
| 401 | Missing or invalid Bearer token |
| 403 | Authenticated but not authorized |
| 404 | Resource not found |
| 422 | Validation error |

---

## MCP Server (AI Integration)

AI assistants can work with tickets through the built-in [Model Context Protocol](https://modelcontextprotocol.io) server (official `laravel/mcp` package) instead of raw REST calls.

**Remote (HTTP):** `POST /mcp/tickets` with the same Bearer API token:

```json
{ "jsonrpc": "2.0", "id": 1, "method": "tools/list", "params": {} }
```

**Local (stdio):** server handle `tickets` (`php artisan mcp:start tickets` or via `mcp:inspector`). No HTTP auth layer, so set `MCP_USER_ID` to the acting user id.

| Tool | What it does |
|------|--------------|
| `get-lookups` | ID lookups for statuses, types, importance, projects, milestones, users. Call first. |
| `list-tickets` | Tickets assigned to you. Optional `status_id`, `unassigned`, `include_pulse`, `per_page`. |
| `get-ticket` | Full detail, notes, and pulse for one `ticket_id`. |
| `get-pulse` | Standalone pulse health state for one `ticket_id`. |
| `create-ticket` | Create a ticket (same required fields as `POST /tickets`). |
| `update-ticket` | Update subject, description, or status on a ticket you own or hold. |
| `add-note` | Note with hours, status change, claim, and full slash-command support (`/close`, `/blocker`, `/decision`, `/action @user`, ...). |
| `reply-to-note` | Reply to a top-level note. |
| `edit-note` | Edit your own note (decisions immutable). |
| `resolve-note` | Resolve a blocker/action thread with a message. |
| `react-to-note` | Toggle thumbsup/eyes reaction. |

---

## See also

- [Installation](installation.md) — how to generate your first token
- Interactive docs: `/api/docs` (Swagger UI)
