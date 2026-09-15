# MCP Server

AI assistants can work with tickets and the knowledge base through the built-in
[Model Context Protocol](https://modelcontextprotocol.io) server (official
`laravel/mcp` package) instead of raw REST calls.

## Connect

**Remote (HTTP):** `POST /mcp/tickets` with the same Bearer API token as the
REST API (generate one at *Profile → Edit Profile → API Token*).

```bash
curl -X POST https://your-domain.com/mcp/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

A `GET /mcp/tickets` returns a JSON-RPC 405 pointing clients at POST.

**Local (stdio):** server handle `tickets` (`php artisan mcp:start tickets`).
There is no HTTP auth layer, so set `MCP_USER_ID` to the acting user's id.

## Notes

- **Call the lookup tools first.** `get-lookups-tool` resolves ticket
  status/type/importance/project/milestone/user names to IDs;
  `get-kb-lookups-tool` resolves KB category and tag IDs.
- **`tools/list` is paginated** (15 per page). Follow `nextCursor` to read the
  rest — all 16 tools span two pages.
- **Errors** come back as `isError: true` with a plain message. Tickets/notes
  and articles you cannot see or edit return a clean "not found" / "cannot
  edit" message rather than leaking existence.
- Reads are visibility-scoped; writes are limited to tickets you own or are
  assigned to, and to KB articles you own or may edit.

## Ticket tools

| Tool | Arguments |
|------|-----------|
| `get-lookups-tool` | none — statuses, types, importance, active projects, milestones, users with IDs. Call first. |
| `list-tickets-tool` | `status_id`, `unassigned` (bool), `include_pulse` (bool), `per_page` (max 100, default 20). Your assigned tickets, newest first. |
| `get-ticket-tool` | `ticket_id` (required) — full detail, visible notes with replies, pulse state. |
| `get-pulse-tool` | `ticket_id` (required) — execution state, blocker, next action, decisions, threads. |
| `create-ticket-tool` | `subject`, `type_id`, `importance_id`, `project_id`, `milestone_id` (required); `description`, `status_id`, `due_at` (YYYY-MM-DD), `estimate`, `storypoints`. Assigned to you. |
| `update-ticket-tool` | `ticket_id` (required); `subject`, `description`, `status_id`. Own/assigned only. |
| `add-note-tool` | `ticket_id` (required); `body` (slash commands supported); `hours` (max 999); `status_id`; `claim` (bool, self-assign). Command-only bodies (e.g. `/close`) apply without storing a note. |
| `reply-to-note-tool` | `ticket_id`, `note_id` (top-level only), `body` (all required). |
| `edit-note-tool` | `ticket_id`, `note_id`, `body` (all required). Own notes only; decisions immutable. |
| `resolve-note-tool` | `ticket_id`, `note_id`, `resolution_message` (all required). Author or assignee only. |
| `react-to-note-tool` | `ticket_id`, `note_id`, `emoji` (`thumbsup`, `eyes`). Toggles. |

### Slash commands (in `add-note-tool` body)

`/close`, `/reopen`, `/status <name>`, `/hours <n>`, `/estimate <n>`,
`/blocker <reason>`, `/decision <text>`, `/action @user <text>` (requires
exactly one `@assignee`), `/assign @user`.

### Pulse fields

`execution_state` (ON TRACK, AT RISK, BLOCKED, IDLE), `is_blocked`,
`blocker_reason`, `next_action`, `latest_decision`, `open_threads`,
`last_activity_at`, `is_stale`. Check pulse before acting — a BLOCKED ticket
needs unblocking, not another status update.

## Knowledge base tools

| Tool | Arguments |
|------|-----------|
| `get-kb-lookups-tool` | none — KB categories and tags with IDs. Call first. |
| `kb-search-tool` | `query`, `category_id`, `status` (`draft`/`verified`/`deprecated`), `tag_id`, `per_page` (max 100). Keyword search over title/body/tags, scoped to what you can see. |
| `get-kb-article-tool` | `slug` (or numeric id, required) — body, category, tags, status, version count. Only articles visible to you. |
| `create-kb-article-tool` | `title`, `body_markdown`, `category_id`, `visibility` (`public`/`internal`/`restricted`), `commit_message`, `tags` (≥1) required; `status` optional. You become the owner. |
| `update-kb-article-tool` | `id`, `title`, `body_markdown`, `category_id`, `visibility`, `commit_message`, `tags` (≥1) required; `status` optional. Owner or permitted editor; creates a new version. |

## Tool naming

Wire names are the kebab-case class name including the `-tool` suffix
(`Str::kebab(class_basename)`), e.g. `get-lookups-tool`, `create-kb-article-tool`.
