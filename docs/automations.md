# Automations

Automations run actions automatically when events occur in the system. Each automation has a trigger, optional conditions, and one or more actions.

**Access:** Automations are admin-only. Navigate to **Admin → Automations**.

## Concepts

- **Trigger** — the event that starts the automation (e.g. ticket created)
- **Conditions** — optional filters that must match for the automation to fire
- **Actions** — what happens when conditions pass (e.g. send a webhook)
- **Rule** — the combination of conditions + actions for a trigger
- **Stop after match** — when enabled, no further automations run for the same event once this one matches

## Creating an automation

1. Navigate to **Admin → Automations**.
2. Click **New Automation**.
3. Fill in:
   - **Name** — descriptive label (required)
   - **Description** — optional notes
   - **Trigger** — select from the available triggers
   - **Enabled** — toggle to activate/deactivate without deleting
   - **Stop after match** — prevents later automations from running once this one fires
4. Build conditions (optional — see [Conditions](#conditions)).
5. Add actions (see [Actions](#actions)).
6. Click **Save**.

## Triggers

| Trigger | When it fires |
|---------|--------------|
| `Ticket Created` | A new ticket is saved for the first time |
| `Ticket Updated` | Any field on an existing ticket is changed |

## Conditions

Conditions filter which events actually trigger actions. Leave conditions empty to match all events.

Conditions use **AND** / **OR** groups:
- **All** — every condition in the group must match
- **Any** — at least one condition must match

Groups can be nested for complex logic.

### Available fields (Ticket Created / Ticket Updated)

| Field | Description |
|-------|-------------|
| `ticket.id` | Ticket ID (integer) |
| `ticket.subject` | Ticket subject (string) |
| `ticket.status` | Status name (string) |
| `ticket.priority` | Importance/priority name (string) |
| `ticket.type` | Type name (string) |
| `ticket.project` | Project name (string) |
| `actor.email` | Email of the user who triggered the event (string) |

**Additional fields for Ticket Updated only:**

| Field | Description |
|-------|-------------|
| `status_id` | Status was changed (use `changed` operator) |
| `importance_id` | Priority was changed (use `changed` operator) |
| `user_id2` | Assignee was changed (use `changed` operator) |

### Operators

| Operator | Use when |
|----------|----------|
| `equals` | Exact match |
| `not_equals` | Does not exactly match |
| `contains` | String contains substring |
| `starts_with` | String starts with value |
| `ends_with` | String ends with value |
| `greater_than` | Numeric comparison |
| `less_than` | Numeric comparison |
| `in` | Value is in a comma-separated list |
| `not_in` | Value is not in a comma-separated list |
| `is_empty` | Field has no value |
| `is_not_empty` | Field has a value |
| `changed` | Field value changed during this update (Ticket Updated only) |

## Actions

Actions execute when conditions pass. Multiple actions can be added to one automation and run in order.

### Send Webhook (`call_webhook`)

Posts a JSON payload to an external URL.

**Config fields:**

| Field | Description |
|-------|-------------|
| URL | The endpoint to POST to (must be HTTPS or an internal host; CGNAT ranges are blocked) |
| Payload | Mustache template — use `{{ticket.subject}}`, `{{ticket.id}}`, `{{actor.email}}`, etc. |

**Example payload template:**

```json
{
  "event": "ticket.created",
  "ticket_id": {{ticket.id}},
  "subject": "{{ticket.subject}}",
  "project": "{{ticket.project}}",
  "actor": "{{actor.email}}"
}
```

### Call API (`call_api`)

Calls an internal API endpoint. Configuration depends on the endpoint targeted.

## Run history

Each automation logs every execution. To view:

1. Navigate to **Admin → Automations**.
2. Click the automation name.
3. The **Runs** tab shows each execution: timestamp, matched conditions, actions executed, and any errors.

## Enable / disable

Toggle the **Enabled** switch on any automation from the index page or edit page without deleting it. Disabled automations never fire.

## Delete

Open the automation and click **Delete**. Run history is deleted with it.

---

## See also

- [API](api.md) — `call_api` action targets the REST API
