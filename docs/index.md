# Tickets — Documentation Index

**Tickets** is a Laravel-based agile ticket tracker with a REST API, knowledge base, automation engine, and AI agent integration.

This index covers all documentation in this directory. Target readers: administrators setting up or managing the system, and AI agents operating via the API.

---

## Concepts

| Document | What it covers |
|----------|---------------|
| [Agile Workflow](agile-workflow.md) | Project → Milestone → Ticket hierarchy, sprint planning, kanban, sprint report |
| [Ticket Pulse](pulse.md) | Execution states (ON TRACK / AT RISK / BLOCKED / IDLE), triage, state transitions |
| [Note Signals](note-signals.md) | Signal types, slash commands, decisions, blockers, actions, reactions, @mentions |
| [Notifications](notifications.md) | Watchers, notification batching, @mentions, muting, notification bell |

---

## System Setup

| Document | What it covers |
|----------|---------------|
| [Installation](installation.md) | Requirements, clone, env config, migrations, seeding, Docker Compose, queue worker |

---

## API

| Document | What it covers |
|----------|---------------|
| [REST API](api.md) | All endpoints, authentication, request/response examples, slash commands, error codes |

Interactive API docs (Swagger UI) are available at `/api/docs` on any running instance.

---

## Administration

| Document | What it covers |
|----------|---------------|
| [Users](users.md) | Admin flag, KB roles, profile fields, avatar, API token generation/revocation, password reset |
| [Automations](automations.md) | Triggers, condition builder, operators, webhook/API actions, run history, enable/disable |
| [CSV Import](csv-import.md) | Bulk ticket creation — column format, required fields, error handling |
| [Projects, Milestones & Releases](projects.md) | Creating projects, milestone sprint reports, burndown charts, release management |
| [Knowledge Base Admin](kb-admin.md) | Categories, tags, article visibility, version history, trash recovery |

---

## Knowledge Base (Author-level)

| Document | What it covers |
|----------|---------------|
| [Knowledge Base](kb.md) | Writing articles, Markdown editor, categories, tags, visibility, version history |
| [Comments & Notes](comments.md) | Note types (message, decision, blocker, action), signal philosophy, real-time log |

---

## Key concepts for AI agents

- **Authentication:** All API requests require `Authorization: Bearer <token>`. Generate tokens at **Profile → Edit Profile → API Token**.
- **Lookups:** Call `GET /api/v1/lookups` first to resolve names (statuses, types, projects, milestones) to IDs before creating tickets.
- **Slash commands:** The `POST /tickets/{id}/note` endpoint accepts slash commands in the `body` field (`/close`, `/assign`, `/status`, `/decision`, `/blocker`, `/action`, etc.).
- **Pulse:** `GET /tickets/{id}/pulse` returns structured execution state (`ON TRACK`, `AT RISK`, `BLOCKED`, `IDLE`) plus open threads, blockers, and next actions — useful for triage agents.
- **Automations:** Webhook actions fire on `ticket.created` and `ticket.updated` events — use these to push data into external systems without polling.
