# AI Workflow with Human-in-the-Middle Decisions

Four AI agents move tickets through a status pipeline. Each agent owns one stage. When an agent needs a decision or hits a blocker, the ticket stops in the Pulse and the human decides. The agents resume from the recorded decision.

## Pipeline

| Stage | Agent | Watches | Hands off with |
|-------|-------|---------|----------------|
| Intake | Product Owner | `new`, `backlog` | `/status active`, `/assign @developer` |
| Build | Code Developer | `active` | `/status testing`, `/action @qa ...` |
| Verify | QA | `testing` | `/status ready to deploy`, `/action @docs ...` |
| Document | Documentation | `ready to deploy` | `/update ...`, `/decide @human ...` |
| Release | Human | Pulse and decision queue | `/decision ...`, resolve blocker, `/status completed` |

The seeded statuses are `new`, `active`, `testing`, `ready to deploy`, `completed`, `waiting`, `reopened`, `backlog`, `duplicate`, and `declined`. Status names are configurable. Closed statuses are recognized by name: `completed`, `done`, `closed`, `resolved`, `duplicate`, and `declined`.

## Agents

### Product Owner

- Watches `new` and `backlog`.
- Clarifies the problem, acceptance criteria, priority, project, and milestone.
- Sets priority with `/priority`, assigns the developer with `/assign`, and moves the ticket to `active`.
- Raises a decision request when scope, priority, or acceptance needs a human call: `/decide @human Should this ship in the current milestone?`.
- Records the accepted scope as a `/decision` note after the human answers.

### Code Developer

- Watches `active` tickets assigned to it.
- Implements the change and logs progress with `/update` and `/hours`.
- Raises `/blocker` for missing access, unclear requirements, or a dependency.
- Creates a review action with `/action @qa ...` and moves the ticket to `testing`.
- Raises a decision request for a technical trade-off: `/decide @human Use the existing queue or add a new one?`.

### QA

- Watches `testing`.
- Runs the acceptance checks and records the result with `/update`.
- On failure, raises `/blocker` or `/action @developer ...` and returns the ticket to `active`.
- On pass, moves the ticket to `ready to deploy` and creates `/action @docs ...`.
- Raises a decision request for a release or risk call: `/decide @human Is the known issue acceptable for this release?`.

### Documentation

- Watches `ready to deploy`.
- Writes or updates the KB article, release note, or runbook, and links the article to the ticket.
- Records the doc update with `/update`.
- Raises a decision request when public wording or scope needs a human call: `/decide @human Approve the public release note?`.
- Leaves the ticket in `ready to deploy` for the human release.

### Human

- Watches the Pulse and the decision queue.
- Answers decision requests by replying with `/decision <answer>` on the request thread.
- Resolves blockers with a resolution message.
- Releases the ticket and moves it to `completed`.
- Can reopen, reassign, or change priority at any point.

## Watching tickets

Each agent uses the MCP server or REST API with its own Bearer token. The agent's working set:

- `list-tickets-tool` with `status_id` for its stage and `include_pulse: true`.
- `list-tickets-tool` with `decision: "me"` for requests waiting on that agent.
- `get-pulse-tool` before acting on a ticket.
- `get-ticket-tool` for the full detail.
- `watch-ticket-tool` to follow a ticket after handoff.

The REST equivalents are `GET /api/v1/tickets?status=<id>&include=pulse`, `GET /api/v1/tickets?decision=me`, `GET /api/v1/tickets/{id}/pulse`, `GET /api/v1/tickets/{id}`, and `POST /api/v1/tickets/{id}/watch`.

The default list is scoped to the agent's assigned tickets. The `status_id` filter narrows the list to the agent's stage. `unassigned=true` lists unassigned tickets for the Product Owner.

## Handoffs

A handoff is a note plus a status change:

- `/status <name>` moves the ticket to the next stage.
- `/assign @agent` changes the assignee.
- `/action @agent <text>` creates the next step and notifies the agent.
- `/update <text>` records the result.

The handoff note stays on the ticket. The next agent reads the ticket and its Pulse before acting.

## Decisions and blockers

The human path uses the same Pulse and note signals as the web UI:

- `/decide @human <question>` creates a `decision_needed` note. The request appears in the ticket Pulse, the navbar "Needs my decision" count, and the human's `decision=me` list.
- The human replies on the thread with `/decision <answer>`. The reply records the decision, closes the request, and updates the Pulse `latest_decision`.
- `/blocker <text>` creates a blocker note. The Pulse shows `BLOCKED`. The blocker stays open until the human or the agent resolves it with a resolution message.
- `/action @agent <text>` creates an action. The Pulse shows the next action and `ON TRACK` while it is open.

The agents check the Pulse before acting. A `BLOCKED` ticket is not moved forward until the blocker is resolved. A ticket with an open decision request is not moved forward until the decision is recorded.

Agents resolve their own blockers with `resolve-note-tool` when the impediment clears. The human resolves a blocker from the ticket page when the call needs a human decision.

## Sample flow

1. A ticket is created in `new`. The Product Owner agent reads it, sets priority, writes acceptance criteria, assigns the developer, and moves it to `active`.
2. The developer implements the change, logs progress, and moves it to `testing` with a QA action.
3. QA finds a regression. It posts a `/blocker` with the failing case, assigns the ticket back to the developer, and returns the status to `active`.
4. The developer fixes the regression, resolves the blocker with the fix, and moves it back to `testing`.
5. QA passes the ticket and moves it to `ready to deploy` with a documentation action.
6. The documentation agent writes the release note and asks the human to approve the public wording with `/decide @human`.
7. The human replies `/decision Approve the wording.`, the decision request closes, and the ticket stays in `ready to deploy`.
8. The human releases the change and moves the ticket to `completed`.

## Setup

1. Create the four agent users and the human decider. Give each agent an API token and MCP access.
2. Confirm the status names. The seeded pipeline is `new`, `active`, `testing`, `ready to deploy`, `completed`.
3. Create the automations. `ticket.created` sends new tickets to the Product Owner. `ticket.updated` sends status changes to the orchestrator, which routes by status to the agent.
4. Use the `call_webhook` action for the automation. The payload includes the ticket id and status; the agent reads the Pulse before acting.
5. Give each agent a prompt with its stage, its MCP tools, and the rule to check the Pulse before acting.
6. Set the human as the decider. The human uses the web UI, the `decision=me` list, and the Pulse.
7. Watch the rate limit. The API and MCP share a 60 requests per minute throttle; the agents back off on 429.

## Coordination software

The workflow runs over MCP or HTTP, so the four roles can live in any agent runtime. The connection guides cover:

- Hermes Agent (Nous Research)
- CrewAI
- LangGraph
- OpenAI Agents SDK
- Claude Agent SDK
- Google ADK
- Mastra
- Microsoft Agent Framework
- OpenClaw
- Claude Code, Claude Desktop, and Claude.ai

See [MCP Server](mcp.md) for client setup and [AI Agents with CrewAI](crewai.md) for a CrewAI example.

## Related documentation

- [MCP Server](mcp.md)
- [REST API](api.md)
- [Ticket Pulse](pulse.md)
- [Note Signals](note-signals.md)
- [Automations](automations.md)
- [AI Agents with CrewAI](crewai.md)
- [Coordinating Specialist Agents](multi-agent.md)
