# Ticket Work Surface

The ticket page combines a unified activity timeline with the Ticket Pulse. Notes, changelog entries, replies, reactions, attachments, and mentions share one timeline. The Pulse summarizes the current execution state above it.

## Note types

| Type | Purpose |
|------|---------|
| `message` | Discussion, questions, and context |
| `decision` | A recorded decision |
| `blocker` | An active impediment |
| `update` | A status update |
| `action` | A committed next step |
| `decision_needed` | A request for a specific person to decide |
| `changelog` | System activity, read-only |

Signal commands and their constraints are documented in [Note Signals](note-signals.md).

## Timeline

- One timeline per ticket. Notes and changelog entries appear in order.
- Comment cards show the author, body, signal styling, reactions, attachments, and mentions.
- Changelog entries are compact and read-only.
- Replies are one level deep. A reply cannot be replied to.
- A thread can be resolved with a resolution message by the thread author, ticket creator, ticket assignee, or decider.
- Editing is author-only and shows an "Edited" indicator.
- Pin, hide, promote, and resolve are moderation actions.
- Reactions are `thumbsup` and `eyes`.

## Composer

- Markdown with live preview.
- Slash commands with an action preview and autocomplete.
- `Cmd+Enter` or `Ctrl+Enter` submits.
- Attachments upload with the note.
- Smart paste detects stack traces, JSON, and URLs.
- Signal nudges suggest a signal type from the text.

## Pulse

The Pulse shows execution state, blockers, open threads, next action, latest decision, and decision requests. See [Ticket Pulse](pulse.md).

## Notifications

Watchers, mentions, the notification bell, the Activity Center, and email preferences are documented in [Notifications](notifications.md).

## Presence

The ticket page shows active viewers as avatars and a composing indicator. Presence uses a heartbeat while the page is open.

## Keyboard shortcuts

| Key | Action |
|-----|--------|
| `?` | Open the composer help |
| `r` | Focus the composer |
| `/` | Start a slash command |
| `j` / `k` | Move to the next or previous timeline entry |
| `Cmd+Enter` / `Ctrl+Enter` | Submit the composer |
| `Escape` | Leave the current input |

## Permissions

- Editing a note: the note author.
- Hiding, pinning, promoting, attaching, or resolving: the note author, ticket creator, ticket assignee, or an admin.
- Replying and reacting: any authenticated team member.

## API

The note endpoints cover adding, replying, editing, resolving, reacting, moderating, and promoting notes. See [REST API](api.md).
