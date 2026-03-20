# Tickets!


Tickets is an agile style ticket tracker using the Laravel PHP framework and Bootstrap 3. Please feel free to add features and grow the project. See the screenshots below!


## Installing


Copy files to your server.


Run composer update to get all of the updated libraries


```
composer update
```


Edit the `.env` to reference the new database


From the command line run the migrations.


```
php artisan migrate
```


Seed the database


```
php artisan db:seed --class=DefaultsSeeder
```


Add default Users: 
* unassigned:nopassword
* admininistrator:password123

```
php artisan db:seed --class=UserSeeder
```


Load in a web browser and enjoy!


## Screenshots


![Alt text](https://raw.githubusercontent.com/velkymx/tickets/master/screenshots/listview.png?raw=true 'List View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/ticket.png?raw=true 'Ticket View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/status.png?raw=true 'Status View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/form.png?raw=true 'Form View')


![Alt text](https://github.com/velkymx/tickets/blob/master/screenshots/milestone.png?raw=true 'Form View')

### Ticket Import Guide
You can bulk-create tickets using the CSV Import tool located at the /tickets/import page. This tool requires you to use the **exact text names** of existing system fields (like 'bug' or 'critical') for a successful import.

**1. Required CSV Columns**
Your CSV file **must** include a header row with these 7 columns, in this exact order:

| **Column Header** | **Data Expected** | **Example Data** | **Description** |
|:-:|:-:|:-:|:-:|
| **Type Name** | Text Name | "bug" | Must be a valid name from the Types list below. |
| **Subject** | Text | "Fix button placement" | The title of the ticket (required). |
| **Details** | Text (in quotes) | "The button overlaps on mobile view." | The full description of the ticket. |
| **Importance Name** | Text Name | "major" or "blocker" | Must be a valid name from the Importances list below. |
| **Status Name** | Text Name | "new" or "completed" | Must be a valid name from the Statuses list below. |
| **Project Name** | Text Name | "Frontend App" | The name of the existing Project. |
| **Assigned To User Name** | Text Name | "Alan Smith" | The full name of the user the ticket should be assigned to (must match a user's name). |

**2. System Names Reference**
Use these exact text names for the corresponding system fields in your CSV:

**Types:**
| **ID** | **Name** |
|:-:|:-:|
| 1 | **bug** |
| 2 | **enhancement** |
| 3 | **task** |
| 4 | **proposal** |

**Importances:**
| **ID** | **Name** |
|:-:|:-:|
| 1 | **trivial** |
| 2 | **minor** |
| 3 | **major** |
| 4 | **critical** |
| 5 | **blocker** |

**Statuses:**
| **ID** | **Name** |
|:-:|:-:|
| 1 | **new** |
| 2 | **active** |
| 3 | **testing** |
| 4 | **ready to deploy** |
| 5 | **completed** |
| 6 | **waiting** |
| 7 | **reopened** |
| 8 | **duplicte** |
| 9 | **declined** |

**3. Example CSV Content (Using Text Names)**
You can copy and paste this example into a plain text file and save it as tickets.csv.

```
Type Name,Subject,Details,Importance Name,Status Name,Project Name,Assigned To User Name
bug,"Fix profile picture upload size","The user's profile image is getting stretched when uploaded. It needs to be resized before saving.",major,new,"Frontend App","Alan Smith"
enhancement,"Add Dark Mode toggle","Create a switch in the settings to allow users to switch between light and dark themes.",critical,active,"Settings API","Jane Doe"
task,"Update welcome message after login","Change the 'Welcome Back' message to include the user's first name for a friendlier greeting.",trivial,completed,"Marketing Site","Alan Smith"
```


## API Documentation

### Overview

The Tickets API provides programmatic access to ticket management functionality. All endpoints require authentication via Bearer token.

**Base URL:** `/api/v1`

**Authentication:** Bearer token in Authorization header

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-domain.com/api/v1/tickets
```

---

### Generate API Token

Users can generate an API token from the application. Tokens are stored hashed and only shown once at generation.

---

### Endpoints

#### Health Check

Check API availability.

```
GET /api/v1/health
```

**Response:**
```json
{
  "status": "ok"
}
```

---

#### Get Lookups

Retrieve available options for tickets (statuses, types, importance levels, projects, milestones).

```
GET /api/v1/lookups
```

**Response:**
```json
{
  "data": {
    "statuses": [
      {"id": 1, "name": "new"},
      {"id": 2, "name": "active"},
      {"id": 3, "name": "testing"},
      {"id": 4, "name": "ready to deploy"},
      {"id": 5, "name": "completed"},
      {"id": 6, "name": "waiting"},
      {"id": 7, "name": "reopened"},
      {"id": 8, "name": "duplicte"},
      {"id": 9, "name": "declined"}
    ],
    "types": [
      {"id": 1, "name": "bug"},
      {"id": 2, "name": "enhancement"},
      {"id": 3, "name": "task"},
      {"id": 4, "name": "proposal"}
    ],
    "importance": [
      {"id": 1, "name": "trivial"},
      {"id": 2, "name": "minor"},
      {"id": 3, "name": "major"},
      {"id": 4, "name": "critical"},
      {"id": 5, "name": "blocker"}
    ],
    "projects": [
      {"id": 1, "name": "Unassigned"}
    ],
    "milestones": [
      {"id": 1, "name": "Unreviewed"},
      {"id": 2, "name": "Future Backlog"},
      {"id": 3, "name": "Backlog"},
      {"id": 4, "name": "Scheduled"}
    ]
  }
}
```

---

#### List My Tickets

Get tickets assigned to the authenticated user.

```
GET /api/v1/tickets
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| status | integer | Filter by status ID |
| unassigned | boolean | If true, return unassigned tickets |

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "subject": "Fix login button",
      "estimate": 3,
      "status": "active",
      "importance": "major",
      "due_at": "2024-01-15",
      "closed_at": null,
      "created_at": "2024-01-01",
      "link": "/api/v1/tickets/1"
    }
  ]
}
```

**Example - Get unassigned tickets:**
```bash
curl -H "Authorization: Bearer TOKEN" \
  "https://your-domain.com/api/v1/tickets?unassigned=true"
```

---

#### Get Ticket Detail

Get full details of a specific ticket including notes.

```
GET /api/v1/tickets/{id}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "subject": "Fix login button",
    "description": "The login button is not responding...",
    "estimate": 3,
    "storypoints": 2,
    "status": "active",
    "type": "bug",
    "importance": "major",
    "milestone": "Sprint 1",
    "project": "Frontend App",
    "assignee": "John Doe",
    "due_at": "2024-01-15",
    "closed_at": null,
    "created_at": "2024-01-01",
    "notes": [
      {
        "id": 1,
        "user": "John Doe",
        "body": "Investigated the issue...",
        "hours": 1.5,
        "created_at": "2024-01-02 10:30:00"
      }
    ]
  }
}
```

---

#### Create Ticket

Create a new ticket assigned to the authenticated user.

```
POST /api/v1/tickets
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| subject | string | Yes | Ticket title |
| description | string | No | Ticket description |
| type_id | integer | No | Ticket type (default: 1) |
| importance_id | integer | No | Importance level (default: 1) |
| project_id | integer | No | Project ID (default: 1) |
| milestone_id | integer | No | Milestone ID (default: 1) |
| due_at | date | No | Due date (Y-m-d) |
| estimate | numeric | No | Estimated hours |
| storypoints | integer | No | Story points |

**Example:**
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"subject": "New Feature", "type_id": 2, "importance_id": 3}' \
  https://your-domain.com/api/v1/tickets
```

**Response:**
```json
{
  "message": "Ticket created successfully",
  "ticket": {
    "id": 5,
    "subject": "New Feature",
    "status": "new",
    "link": "/api/v1/tickets/5"
  }
}
```

---

#### Add Note / Update Ticket

Add a note to a ticket and optionally update status, log hours, or claim the ticket.

```
POST /api/v1/tickets/{id}/note
```

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| body | string | No | Note content |
| status_id | integer | No | Update ticket status |
| hours | numeric | No | Time logged |
| claim | boolean | No | Assign ticket to authenticated user |

**Example - Claim ticket and add work note:**
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Started working on this", "hours": 1, "claim": true}' \
  https://your-domain.com/api/v1/tickets/1/note
```

**Example - Move to testing:**
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Ready for QA", "status_id": 3}' \
  https://your-domain.com/api/v1/tickets/1/note
```

**Example - Complete ticket:**
```bash
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Fixed and deployed", "status_id": 5, "hours": 4}' \
  https://your-domain.com/api/v1/tickets/1/note
```

**Response:**
```json
{
  "message": "Note added successfully",
  "ticket": {
    "id": 1,
    "status": "completed",
    "assignee": "John Doe"
  }
}
```

---

### Workflow Example

A typical AI development workflow using the API:

```bash
# 1. Get available options
curl -H "Authorization: Bearer TOKEN" /api/v1/lookups

# 2. Get unassigned tickets
curl -H "Authorization: Bearer TOKEN" "/api/v1/tickets?unassigned=true"

# 3. Claim a ticket and start working
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Starting work", "hours": 0, "claim": true}' \
  /api/v1/tickets/1/note

# 4. Get ticket details with notes
curl -H "Authorization: Bearer TOKEN" /api/v1/tickets/1

# 5. Log work progress
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Completed feature implementation", "hours": 2}' \
  /api/v1/tickets/1/note

# 6. Move to testing
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Ready for QA", "status_id": 3}' \
  /api/v1/tickets/1/note

# 7. Complete the ticket
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "QA passed, deploying", "status_id": 5, "hours": 0.5}' \
  /api/v1/tickets/1/note
```

---

## License

Tickets! is open-sourced software licensed under the [MIT license](LICENSE.md).

