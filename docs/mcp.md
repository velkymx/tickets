# MCP Server

AI assistants can work with tickets and the knowledge base through the built-in
[Model Context Protocol](https://modelcontextprotocol.io) server (official
`laravel/mcp` package) instead of raw REST calls.

## Two ways to connect

Everything below is just an adapter onto one of these two transports. Pick the
row that matches your tool, then use the matching snippet further down.

| Transport | Endpoint / handle | Auth | Who it's for |
|-----------|-------------------|------|--------------|
| **Streamable HTTP** (remote) | `POST /mcp/tickets` | `Authorization: Bearer <api_token>` per request — each user's own token | Any remote/cloud MCP client; multi-user |
| **stdio** (local) | handle `tickets` (`php artisan mcp:start tickets`) | none — set `MCP_USER_ID` to the acting user's id | One on-machine assistant; single user |

Generate a token at *Profile → Edit Profile → API Token*. The HTTP transport is
Streamable HTTP (MCP spec) — send `Accept: application/json, text/event-stream`.
A `GET /mcp/tickets` returns a JSON-RPC 405 pointing clients at POST.

Smoke test:

```bash
curl -X POST https://your-domain.com/mcp/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}'
```

> **Static Bearer vs OAuth.** This server authenticates with a static Bearer
> API token (`api.token` middleware), not OAuth. Clients that let you set a
> request header (Claude Code, Cursor, Hermes, raw clients) connect directly.
> Clients whose connector UI only offers OAuth (the Claude.ai web/Team/Enterprise
> "custom connector" flow) cannot attach this endpoint as-is — see that section.

## Connecting from agent tools

### Claude Code (official)

Add the remote server with a header, or drop the JSON into `.mcp.json`
([docs](https://code.claude.com/docs/en/mcp)):

```bash
claude mcp add --transport http tickets https://your-domain.com/mcp/tickets \
  --header "Authorization: Bearer YOUR_TOKEN"
# share with a repo: add  --scope project  (writes .mcp.json)
```

```jsonc
// .mcp.json  ("streamable-http" is an accepted alias for "http")
{
  "mcpServers": {
    "tickets": {
      "type": "http",
      "url": "https://your-domain.com/mcp/tickets",
      "headers": { "Authorization": "Bearer ${TICKETS_TOKEN}" }
    }
  }
}
```

### Claude Desktop / stdio-only clients (mcp-remote bridge)

Desktop-style clients that only speak stdio and have no header field reach the
Bearer endpoint through the `mcp-remote` bridge:

```jsonc
// claude_desktop_config.json
{
  "mcpServers": {
    "tickets": {
      "command": "npx",
      "args": [
        "-y", "mcp-remote",
        "https://your-domain.com/mcp/tickets",
        "--header", "Authorization: Bearer YOUR_TOKEN"
      ]
    }
  }
}
```

### Claude.ai — Team / Enterprise / Work (custom connector)

The web connector flow is **OAuth-only** — there is no static-token field, and
Claude connects from Anthropic's cloud, so the URL must be public HTTPS (no
localhost) ([docs](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp)).
Steps: an Owner opens *Organization settings → Connectors → Add → Custom → Web*,
enters the MCP URL, sets OAuth client id/secret under *Advanced settings*; each
member then *Customize → Connectors → Connect*.

Because this endpoint uses a static Bearer token, to use it here you must either
put an OAuth 2.1 layer in front (`laravel/mcp` supports `Mcp::oauthRoutes()` with
Passport) or expose a public bridge that injects the header. Until then, use
Claude Code / Desktop / a raw client instead.

### Hermes Agent (Nous Research)

Config lives at `~/.hermes/config.yaml` under `mcp_servers`: a remote server
uses `url` + `headers`, stdio uses `command`/`args`. Narrow the surface with
`tools.include`, then run `/reload-mcp` inside Hermes to pick up the change
([docs](https://hermes-agent.nousresearch.com/docs/user-guide/features/mcp)).

```yaml
mcp_servers:
  tickets:
    url: "https://your-domain.com/mcp/tickets"
    headers:
      Authorization: "Bearer YOUR_TOKEN"
    tools:
      include: [get-lookups-tool, list-tickets-tool, get-ticket-tool, add-note-tool]
```

### OpenClaw

Servers live under [`mcp.servers.<name>`](https://docs.openclaw.ai/tools/mcp): a
remote server takes `url` + `transport: "streamable-http"`; a stdio server takes
`command`/`args` + `transport: "stdio"`. The config offers `auth: "oauth"` and a
secret store but no documented static-header field, so reach this Bearer
endpoint through the `mcp-remote` bridge as a stdio server (keep the token in the
secret store, not a literal):

```json5
{ mcp: { servers: { tickets: {
  command: "npx",
  args: ["-y", "mcp-remote", "https://your-domain.com/mcp/tickets",
         "--header", "Authorization: Bearer YOUR_TOKEN"],
  transport: "stdio",
  enabled: true,
} } } }
```

### Any other MCP client

If it accepts a Streamable-HTTP URL plus custom headers, point it at
`https://your-domain.com/mcp/tickets` with `Authorization: Bearer <token>`. If it
only speaks stdio, wrap that same URL with `npx -y mcp-remote … --header` as
above. Vendor config keys drift — confirm against each tool's own current docs;
the links here were checked September 2026.

## Connecting from agent frameworks (SDKs)

All of these speak native Streamable HTTP, so they take our `url` plus an
`Authorization: Bearer` header directly — no bridge needed. Replace the URL and
token; keep the token in an env var, not a literal. Call the lookup tools first
(see below) and consider filtering to the handful of tools an agent needs.

### LangGraph (`langchain-mcp-adapters`)

`transport` is `streamable_http` (underscore).
[docs](https://github.com/langchain-ai/langchain-mcp-adapters)

```python
from langchain_mcp_adapters.client import MultiServerMCPClient
from langgraph.prebuilt import create_react_agent

client = MultiServerMCPClient({
    "tickets": {
        "transport": "streamable_http",
        "url": "https://your-domain.com/mcp/tickets",
        "headers": {"Authorization": f"Bearer {TICKETS_TOKEN}"},
    }
})
tools = await client.get_tools()
agent = create_react_agent("anthropic:claude-sonnet-5", tools)
```

### CrewAI (`crewai-tools`)

`transport` is `streamable-http` (hyphen); `MCPServerAdapter` is a context
manager. [docs](https://docs.crewai.com/en/mcp/overview) — see also
[`docs/crewai.md`](crewai.md).

```python
from crewai_tools import MCPServerAdapter

server = {
    "url": "https://your-domain.com/mcp/tickets",
    "transport": "streamable-http",
    "headers": {"Authorization": f"Bearer {TICKETS_TOKEN}"},
}
with MCPServerAdapter(server) as tools:
    agent = Agent(role="Support", goal="Triage tickets", tools=tools)
```

### OpenAI Agents SDK

[docs](https://openai.github.io/openai-agents-python/mcp/)

```python
from agents import Agent
from agents.mcp import MCPServerStreamableHttp

async with MCPServerStreamableHttp(
    name="tickets",
    params={
        "url": "https://your-domain.com/mcp/tickets",
        "headers": {"Authorization": f"Bearer {TICKETS_TOKEN}"},
    },
    cache_tools_list=True,
) as server:
    agent = Agent(name="Support", instructions="Triage tickets", mcp_servers=[server])
```

### Claude Agent SDK

Server type is `http`. [docs](https://docs.claude.com/en/docs/agent-sdk/mcp)

```python
from claude_agent_sdk import ClaudeAgentOptions, query

options = ClaudeAgentOptions(
    mcp_servers={
        "tickets": {
            "type": "http",
            "url": "https://your-domain.com/mcp/tickets",
            "headers": {"Authorization": f"Bearer {TICKETS_TOKEN}"},
        }
    },
    allowed_tools=["mcp__tickets__list-tickets-tool", "mcp__tickets__get-ticket-tool"],
)
```

### Google ADK

[docs](https://adk.dev/tools-custom/mcp-tools/)

```python
from google.adk.agents import LlmAgent
from google.adk.tools.mcp_tool import MCPToolset, StreamableHTTPConnectionParams

toolset = MCPToolset(
    connection_params=StreamableHTTPConnectionParams(
        url="https://your-domain.com/mcp/tickets",
        headers={"Authorization": f"Bearer {TICKETS_TOKEN}"},
    )
)
agent = LlmAgent(model="gemini-2.5-pro", name="support", tools=[toolset])
```

### Mastra (`@mastra/mcp`)

TypeScript; headers ride on `requestInit`. [docs](https://mastra.ai/reference/tools/mcp-client)

```ts
import { MCPClient } from "@mastra/mcp";

const mcp = new MCPClient({
  servers: {
    tickets: {
      url: new URL("https://your-domain.com/mcp/tickets"),
      requestInit: {
        headers: { Authorization: `Bearer ${process.env.TICKETS_TOKEN}` },
      },
    },
  },
});
const tools = await mcp.getTools();
```

### Microsoft Agent Framework

[docs](https://learn.microsoft.com/en-us/python/api/agent-framework-core/agent_framework.mcpstreamablehttptool)

```python
from agent_framework import MCPStreamableHTTPTool, ChatAgent

tickets = MCPStreamableHTTPTool(
    name="tickets",
    url="https://your-domain.com/mcp/tickets",
    headers={"Authorization": f"Bearer {TICKETS_TOKEN}"},
)
async with tickets:
    agent = ChatAgent(chat_client=client, name="support", tools=tickets)
```

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
