# AI Agents with CrewAI

Tickets can act as the coordination layer for AI agents.

The built-in [Model Context Protocol](https://modelcontextprotocol.io/) server gives AI clients access to tickets and the knowledge base, while the REST API provides the same capabilities to custom agents and automation.

This makes Tickets useful as more than a project management application. It can serve as the shared workspace where humans and AI agents coordinate work, record decisions, surface blockers, and track execution.

## Why use Tickets as an AI coordination layer?

AI agents are good at performing focused tasks. They are much less useful when every agent has its own isolated context and there is no shared record of what happened.

Tickets provides that shared context.

Agents can:

* Read assigned work
* Create and update tickets
* Add notes and replies
* Record decisions
* Report blockers
* Assign actions
* Claim work
* Log hours
* Resolve discussion threads
* Read Ticket Pulse
* Search and update knowledge base articles

Humans see the same work in the same application.

Instead of building a separate coordination system for every AI workflow, Tickets becomes the system of record for the work.

## Architecture

A typical AI-enabled workflow looks like this:

```text
                    +------------------+
                    |      Humans      |
                    +--------+---------+
                             |
                             v
                    +------------------+
                    |     Tickets      |
                    |                  |
                    | Projects         |
                    | Issues           |
                    | Sprints          |
                    | Knowledge Base   |
                    | Ticket Pulse     |
                    +---+----------+---+
                        ^          ^
                        |          |
              REST API  |          |  MCP
                        |          |
              +---------+--+    +--+---------+
              | AI Agents |    | AI Clients |
              |           |    |             |
              | CrewAI    |    | MCP Clients |
              | Custom    |    | AI Assistants|
              +-----------+    +-------------+
```

Tickets remains the shared coordination layer while different AI clients and agents perform specialized work.

## MCP or REST?

Use MCP when your AI client supports the Model Context Protocol.

Use the REST API when you are building your own agent, automation, service, or integration.

Both interfaces use the same underlying Tickets data and permission model.

### MCP

The remote MCP endpoint is:

```text
POST /mcp/tickets
```

It uses the same Bearer API token as the REST API.

For local development, Tickets also provides a stdio MCP server:

```bash
php artisan mcp:start tickets
```

See the complete [MCP Server documentation](mcp.md) for connection details and the available tools.

### REST API

The REST API is available at:

```text
/api/v1
```

All API endpoints require a Bearer token.

See the [REST API documentation](api.md) for the complete API reference.

## CrewAI

[CrewAI](https://www.crewai.com/) is a Python framework for building role-based AI agents that collaborate as a crew.

Tickets works well as the shared system of record for a CrewAI workflow.

For example, a crew might contain:

* A triage agent that reviews new tickets
* An execution agent that monitors blocked and at-risk work
* A documentation agent that maintains the knowledge base
* A reporting agent that summarizes project activity

The agents can operate independently while Tickets maintains the shared state.

## Example: Ticket Triage Crew

The following example creates a simple CrewAI crew with two agents.

The first agent reviews new tickets and flags missing information.

The second monitors Ticket Pulse and reports blocked or at-risk work.

```python
import os
import requests

from crewai import Agent, Crew, Task
from crewai_tools import tool


BASE_URL = os.environ["TICKETS_URL"].rstrip("/") + "/api/v1"
TOKEN = os.environ["TICKETS_TOKEN"]


@tool("tickets_api")
def tickets_api(method: str, path: str, body: str = "") -> str:
    """Call the Tickets REST API.

    method:
        HTTP method such as GET, POST, or PATCH.

    path:
        API path such as /tickets or /tickets/123/pulse.

    body:
        Optional JSON request body.
    """
    url = f"{BASE_URL}{path}"

    headers = {
        "Authorization": f"Bearer {TOKEN}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }

    response = requests.request(
        method=method,
        url=url,
        headers=headers,
        data=body or None,
        timeout=30,
    )

    response.raise_for_status()

    return response.text


triage_agent = Agent(
    role="Ticket Triage Specialist",
    goal=(
        "Review new tickets, identify missing information, and ensure "
        "each ticket has enough detail for the development team to act."
    ),
    backstory=(
        "You are responsible for maintaining a clean and actionable backlog. "
        "You look for unclear subjects, incomplete descriptions, missing context, "
        "and other problems that would prevent a developer from starting work."
    ),
    tools=[tickets_api],
    verbose=True,
)


pulse_agent = Agent(
    role="Execution Monitor",
    goal=(
        "Monitor active tickets and identify work that is blocked or at risk "
        "before it becomes a larger problem."
    ),
    backstory=(
        "You monitor Ticket Pulse and help the team identify blocked work, "
        "stale tickets, unresolved threads, and other execution problems."
    ),
    tools=[tickets_api],
    verbose=True,
)


triage_task = Task(
    description=(
        "Use the Tickets API to find new tickets. Review each ticket for "
        "a clear subject and useful description. If information is missing, "
        "add a note explaining what the team needs. If the ticket is actionable, "
        "record a note confirming that it is ready for review."
    ),
    expected_output=(
        "A summary of the tickets reviewed, including tickets that were "
        "ready and tickets that require additional information."
    ),
    agent=triage_agent,
)


pulse_task = Task(
    description=(
        "Review active tickets and inspect their Ticket Pulse information. "
        "Identify tickets that are BLOCKED or AT RISK. For each affected ticket, "
        "add a note describing the problem and what should happen next."
    ),
    expected_output=(
        "A summary of blocked and at-risk tickets that were identified "
        "and reported to the team."
    ),
    agent=pulse_agent,
)


crew = Crew(
    agents=[triage_agent, pulse_agent],
    tasks=[triage_task, pulse_task],
    verbose=True,
)


result = crew.kickoff()

print(result)
```

Set the connection details before running the crew:

```bash
export TICKETS_URL="https://tickets.example.com"
export TICKETS_TOKEN="your_api_token"

python crew.py
```

The API token should belong to the user whose permissions you want the agents to operate under.

## Agents should use Ticket Pulse

Ticket Pulse is particularly useful for agent workflows because it gives agents execution context rather than just raw ticket fields.

A ticket can expose:

```text
execution_state
is_blocked
blocker_reason
next_action
latest_decision
open_threads
last_activity_at
is_stale
```

An agent can therefore make a better decision than:

> "This ticket is still open."

It can reason about:

> "This ticket is blocked, the blocker has been open for two days, there is an unresolved thread, and the next action has not been recorded."

That distinction is important when using AI for project coordination.

## Agents should leave their work in Tickets

An agent should not perform important work silently.

Use Tickets to record:

* What the agent found
* Decisions it made
* Blockers it discovered
* Actions it recommends
* Work it completed
* Questions that require human input

For example:

```text
/blocker Waiting for API credentials from the vendor
```

or:

```text
/decision Use the existing authentication middleware instead of introducing a second authentication layer.
```

or:

```text
/action @Jane Review the proposed database migration.
```

The result is an auditable project history that both humans and other agents can read.

## Multiple agents, one coordination layer

A larger deployment could use several specialized agents:

```text
                         Tickets
                            |
          +-----------------+-----------------+
          |                 |                 |
      Triage Agent     Development Agent   QA Agent
          |                 |                 |
          +-----------------+-----------------+
                            |
                     Documentation Agent
```

Each agent can have a narrow responsibility while Tickets maintains the shared state.

For example:

1. A triage agent creates and classifies work.
2. A development agent claims an issue and performs implementation work.
3. A QA agent reviews the resulting work.
4. A documentation agent updates the knowledge base.
5. Ticket Pulse exposes anything that becomes blocked or stale.
6. Humans intervene when an agent needs a decision.

This is where Tickets becomes more interesting than simply exposing an AI API.

It provides a shared coordination model for human and machine work.

## MCP clients

Any MCP-compatible client can connect to the Tickets MCP server.

Remote clients connect to:

```text
POST https://your-domain.com/mcp/tickets
```

using the same Bearer token authentication as the REST API.

Local clients can use the stdio server:

```bash
php artisan mcp:start tickets
```

See [MCP Server](mcp.md) for the complete list of tools, arguments, permissions, and connection details.

## Hermes and other agent clients

Tickets can also serve as the coordination layer for other agent runtimes and clients that can consume MCP or call HTTP APIs.

For a client that supports MCP:

```text
AI Client
    |
    | MCP
    v
Tickets
    |
    +-- Issues
    +-- Sprints
    +-- Notes
    +-- Knowledge Base
    +-- Ticket Pulse
```

For an agent runtime that uses HTTP tools:

```text
Agent Runtime
    |
    | REST API
    v
Tickets
```

This means the same Tickets installation can coordinate work from multiple AI systems instead of locking the project into a single AI framework.

When a client has a documented integration pattern, add it here with a working example.

## Security and permissions

AI agents should be treated like any other user or integration.

Use a dedicated API token with only the permissions the agent needs.

Tickets applies visibility and write restrictions to MCP operations. Agents should not be given broader access than the human account they represent requires.

Do not place API tokens directly in source code or commit them to a repository.

Use environment variables or a secrets manager:

```bash
export TICKETS_TOKEN="..."
```

## Building your own agent

The REST API and MCP server are intentionally complementary.

You can build an agent that:

1. Reads a ticket
2. Checks Ticket Pulse
3. Searches the knowledge base
4. Determines what information is missing
5. Performs an action
6. Records the decision
7. Updates the ticket
8. Leaves the work available for the next human or agent

The important part is that the coordination state remains in Tickets.

The agent can change. The model can change. The orchestration framework can change.

The project history remains.

## Related documentation

* [MCP Server](mcp.md)
* [REST API](api.md)
* [Ticket Pulse](pulse.md)
* [Agile Workflow](agile-workflow.md)
* [Knowledge Base](kb-admin.md)
