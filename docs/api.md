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

**Optional fields:** `description`, `status_id`, `assignee_id`, `due_at`, `estimate`, `storypoints`. The ticket is assigned to you by default; pass `assignee_id` to assign someone else, or `assignee_id: null` to leave it unassigned.

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
    "assignee_id": 7,
    "due_at": "2026-06-01"
  }' \
  https://your-app.example.com/api/v1/tickets
```

---

### Update Ticket

```
PUT /tickets/{id}
PATCH /tickets/{id}
```

Updates any subset of a ticket you own or are assigned to. Send only the fields you want to change.

**Fields:** `subject`, `description`, `status_id`, `assignee_id`, `type_id`, `importance_id`, `project_id`, `milestone_id`, `due_at`, `estimate`, `storypoints`.

```bash
curl -X PUT \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"assignee_id": 7, "importance_id": 5, "due_at": "2026-07-01"}' \
  https://your-app.example.com/api/v1/tickets/42
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

### Add Note

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

### Moderate a Note

```
POST /tickets/{id}/notes/{noteId}/moderate
```

Pins or hides a note on a ticket you own or are assigned to. `action` is one of `pin`, `unpin`, `hide`, `unhide`.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"action": "pin"}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99/moderate
```

---

### Promote a Note

```
POST /tickets/{id}/notes/{noteId}/promote
```

Turns a plain message note into a `decision`, `blocker`, or `action`. Only message notes can be promoted; decisions need 20+ characters, and actions need exactly one `@assignee` (a mention in the body or the `assignee` field).

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"type": "action", "assignee": "jane"}' \
  https://your-app.example.com/api/v1/tickets/42/notes/99/promote
```

---

### Watch a Ticket

```
POST /tickets/{id}/watch
```

Watch, unwatch, mute, or unmute a ticket for yourself. `action` is one of `watch` (default), `unwatch`, `mute`, `unmute`. Any ticket you can view may be watched.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"action": "mute"}' \
  https://your-app.example.com/api/v1/tickets/42/watch
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

## Knowledge Base

All KB endpoints use the same Bearer token and enforce article visibility — you only see and edit what you are allowed to.

### KB Lookups

```
GET /kb/lookups
```

Returns knowledge base categories and tags with IDs. Call first to resolve names before creating or updating articles.

### Search Articles

```
GET /kb/articles
```

Keyword search over title, body, and tags, scoped to what you can see. Query params: `query`, `category_id`, `status` (`draft`/`verified`/`deprecated`), `tag_id`, `per_page` (max 100). Returns a paginated `data` + `meta` envelope.

```bash
curl -H "Authorization: Bearer <token>" \
  "https://your-app.example.com/api/v1/kb/articles?query=deploy&status=verified"
```

### Get Article

```
GET /kb/articles/{idOrSlug}
```

Fetch a single article by numeric id or slug (body, category, tags, status, version count). Returns 404 for articles you cannot see.

### Create Article

```
POST /kb/articles
```

**Required:** `title`, `body_markdown`, `category_id`, `visibility` (`public`/`internal`/`restricted`), `commit_message`, `tags` (array, ≥1). **Optional:** `status`. You become the article owner.

```bash
curl -X POST \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Deploy runbook",
    "body_markdown": "# Steps\n1. ...",
    "category_id": 2,
    "visibility": "internal",
    "commit_message": "Initial draft",
    "tags": [3, 5]
  }' \
  https://your-app.example.com/api/v1/kb/articles
```

### Update Article

```
PUT /kb/articles/{id}
PATCH /kb/articles/{id}
```

Same fields as create. Only the owner or a permitted editor may update; each update creates a new version.

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

AI assistants can work with tickets and the knowledge base through the built-in
[Model Context Protocol](https://modelcontextprotocol.io) server (official
`laravel/mcp` package) instead of raw REST calls — `POST /mcp/tickets` with the
same Bearer token, or the local stdio handle `tickets`.

The full MCP reference — connection, all 16 tools with arguments, slash
commands, pulse fields, and error conventions — lives in **[mcp.md](mcp.md)**.

---

## See also

- [Installation](installation.md) — how to generate your first token
- Interactive docs: `/api/docs` (Swagger UI)
