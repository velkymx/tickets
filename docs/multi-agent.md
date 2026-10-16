# Coordinating Specialist Agents Through Tickets

One tickets server can coordinate several specialist agents the way Hermes
or Grokbot coordinate tools: each specialist gets its own API token (its own
identity), and tickets plus status plus notes plus pulse become the shared
state. No direct agent-to-agent channel needed.

## Setup

1. Create one user per specialist (planner, coder, reviewer) and generate an
   API token for each at *Profile → Edit Profile → API Token*.
2. Connect each agent to `POST /mcp/tickets` with its own Bearer token
   (see `docs/mcp.md` for transport snippets).
3. Point every specialist at the workflow prompts (`triage-ticket-prompt`,
   `standup-digest-prompt`, `close-out-ticket-prompt`) so they follow the
   same playbook.

## Example flow: ship a feature

**Planner** files the work and frames the decisions:

- `create-ticket-tool` with subject, description, and acceptance criteria.
- `add-note-tool` with `/decision` notes for each open question, so the
  threads awaiting calls show up in pulse.

**Coder** claims and builds:

- `list-tickets-tool` with `unassigned` for open tickets in scope.
- `update-ticket-tool` to claim (assignee) and move status as work progresses.
- `add-note-tool` with `/action` for next steps and `/blocker` the moment
  something stalls. Blocked means stop and report, not push through.

**Reviewer** verifies and closes:

- `get-pulse-tool` first. Open threads are decisions awaiting a human call:
  summarize what is asked, do not decide silently.
- `resolve-note-tool` per thread with one resolution message each.
- `update-ticket-tool` to a closed status only when threads are resolved.

**Coordinator** (Hermes/Grokbot style loop) watches the milestone:

- `standup-digest-prompt` each cycle: blocked items with owner, at-risk
  items with why, decisions needed listed last.
- Reassign stuck tickets with `update-ticket-tool` instead of nagging.

## Rules that keep it working

- Pulse before acting. A BLOCKED ticket needs unblocking, not more updates.
- One owner per ticket at a time. Reassign explicitly, never assume.
- Every handoff leaves a note. If it is not in notes, the next agent cannot
  see it in pulse.
- Close-out order: resolve threads, record the `/decision` changelog, then
  move status.

## Escalating questionable calls to humans

Agents should surface judgment calls, not bury them. Anything ambiguous,
irreversible, or contested goes to a human through the same ticket state.

Escalation triggers:

- Two agents disagree (reviewer rejects coder's approach twice).
- The call is irreversible (data deletion, production deploy, closing with
  open threads).
- Confidence is low (pulse shows stale signals, requirements conflict).

How to escalate:

1. `add-note-tool` with `/blocker` stating the question plainly plus the
   options on the table. Open threads surface it in pulse for the next
   human who opens the ticket.
2. Leave status alone. A stalled ticket with a clear question beats a
   closed ticket with a guessed answer.
3. `update-ticket-tool` to assign the ticket to the human owner so it shows
   in their list.

Worked example: coder and reviewer disagree on a migration strategy. The
reviewer posts a `/blocker` note framing option A versus option B with
trade-offs, assigns the ticket to the tech lead, and stops. The lead
resolves the thread from the ticket page, which clears it from pulse and
unblocks the coder. No side channels, the whole argument stays on the
ticket for the next agent to read.
