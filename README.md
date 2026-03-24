# Tickets!

Tickets is an open source agile ticket tracker built with Laravel. It includes a Kanban board, knowledge base, real-time collaboration tools, and a REST API.

## Features

### Ticket Management
- Create, edit, clone, and batch-update tickets with full metadata (type, status, importance, project, milestone, assignee, due date, story points, estimates)
- Kanban board with drag-and-drop status changes (powered by SortableJS)
- Multi-filter list view with search, pagination, and per-page control
- CSV import for bulk ticket creation
- Ticket Pulse — real-time execution state (ON TRACK, AT RISK, BLOCKED, IDLE) with blocker surfacing, decision tracking, and open thread monitoring

### Notes & Activity
- Threaded notes with reply support, pinning, hiding, and emoji reactions
- Signal types: message, decision, blocker, action, update (auto-generated changelog entries)
- Slash commands: `/decision`, `/blocker`, `/action`, `/assign`, `/status`, `/hours`, `/estimate`, `/close`, `/reopen`, `/pin`, `/update`
- @mention autocomplete with keyboard navigation
- Smart paste detection (auto-formats stack traces, JSON, and URLs)
- Markdown toolbar with live preview
- File attachments on notes and KB articles

### Knowledge Base
- Article management with Markdown (EasyMDE editor), categories, and tags
- Version history with diff comparison and restore
- Article visibility: public, internal, restricted (with per-user permissions)
- Full-text search across articles, categories, and tags
- Quick-create categories and tags inline during article creation
- Soft deletes with admin trash recovery

### Collaboration
- Live presence indicators showing who's viewing a ticket (with composing status)
- Watcher system with email + database notifications
- Notification batching — rapid updates to the same ticket are grouped into digest emails
- Notification bell with unread count and activity feed
- Cross-reference linking: `#123` links to tickets, `kb:slug` links to KB articles

### Other
- Milestones with sprint reports, burndown charts, and progress tracking
- Releases with ticket association
- Projects with progress tracking and filtered views
- Theme support: Light (Simplex), Dark (Darkly), or Auto (OS preference)
- User profiles with Gravatar avatars
- REST API with token authentication

## Requirements

- PHP 8.2+
- Composer
- A database (MySQL, PostgreSQL, SQLite)

## Installation

```bash
git clone https://github.com/velkymx/tickets.git
cd tickets
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` to configure your database connection, then run:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

Start the development server:

```bash
php artisan serve
```

### Default Users

| Email | Password |
|-------|----------|
| admin@example.com | password123 |

## API

All API endpoints require a Bearer token. Generate one from your profile page.

**Base URL:** `/api/v1`

```
GET  /api/v1/health                          # Health check
GET  /api/v1/lookups                         # Available statuses, types, etc.
GET  /api/v1/tickets                         # List tickets
POST /api/v1/tickets                         # Create ticket
GET  /api/v1/tickets/{id}                    # Get ticket detail
POST /api/v1/tickets/{id}/note               # Add note / update ticket
POST /api/v1/tickets/{id}/notes/{noteId}/react    # Toggle reaction
POST /api/v1/tickets/{id}/notes/{noteId}/reply    # Reply to note
PUT  /api/v1/tickets/{id}/notes/{noteId}          # Edit note
POST /api/v1/tickets/{id}/notes/{noteId}/resolve  # Resolve thread
GET  /api/v1/tickets/{id}/pulse              # Get ticket pulse
```

See [API Documentation](#api-documentation) below for full details.

## CSV Import

Bulk-create tickets via CSV at `/tickets/import`. Your CSV must include these columns in order:

| Column | Description |
|--------|-------------|
| Type Name | bug, enhancement, task, proposal |
| Subject | Ticket title |
| Details | Ticket description |
| Importance Name | trivial, minor, major, critical, blocker |
| Status Name | new, active, testing, ready to deploy, completed, waiting, reopened, duplicate, declined |
| Project Name | Must match an existing project |
| Assigned To User Name | Must match a user's full name |

Example:

```csv
Type Name,Subject,Details,Importance Name,Status Name,Project Name,Assigned To User Name
bug,"Fix profile picture upload","The image is getting stretched on upload.",major,new,"Frontend App","Alan Smith"
```

## API Documentation

### Authentication

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-domain.com/api/v1/tickets
```

### Endpoints

#### Health Check

```
GET /api/v1/health
```

```json
{ "status": "ok" }
```

#### Get Lookups

```
GET /api/v1/lookups
```

Returns available statuses, types, importance levels, projects, milestones, releases, and users.

#### List Tickets

```
GET /api/v1/tickets
```

| Parameter | Type | Description |
|-----------|------|-------------|
| status | integer | Filter by status ID |
| unassigned | boolean | Return unassigned tickets |
| pulse | boolean | Include pulse data |

#### Get Ticket Detail

```
GET /api/v1/tickets/{id}
```

Returns full ticket details including notes with reactions, replies, attachments, and mentions.

#### Create Ticket

```
POST /api/v1/tickets
```

| Field | Type | Required |
|-------|------|----------|
| subject | string | Yes |
| description | string | No |
| type_id | integer | No |
| importance_id | integer | No |
| project_id | integer | No |
| milestone_id | integer | No |
| due_at | date | No |
| estimate | numeric | No |
| storypoints | integer | No |

#### Add Note / Update Ticket

```
POST /api/v1/tickets/{id}/note
```

| Field | Type | Description |
|-------|------|-------------|
| body | string | Note content (supports slash commands) |
| status_id | integer | Update ticket status |
| hours | numeric | Log time |
| claim | boolean | Assign ticket to yourself |

#### Resolve Thread

```
POST /api/v1/tickets/{id}/notes/{noteId}/resolve
```

| Field | Type | Required |
|-------|------|----------|
| resolution_message | string | Yes |

#### Get Ticket Pulse

```
GET /api/v1/tickets/{id}/pulse
```

Returns execution state, latest blocker, next action, latest decision, and open threads.

## License

Tickets is open-sourced software licensed under the [MIT license](LICENSE.md).
