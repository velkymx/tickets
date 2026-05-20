# Notifications

Tickets sends notifications when meaningful activity happens on tickets and milestones you're watching.

## Watchers

A **watcher** subscribes to a ticket or milestone and receives notifications when relevant changes occur.

### Watch a ticket

Open a ticket and click **Watch** in the sidebar. Click again to unwatch.

### Watch a milestone

Navigate to the milestone page and click **Watch**. Click again to unwatch.

## What triggers a notification

Not every change sends a notification. Only **meaningful** changes notify watchers:

| Activity | Notifies watchers? |
|----------|--------------------|
| New note added | Yes |
| Status changed | Yes |
| Assignee changed | Yes |
| Note resolved | Yes |
| @mention in a note | Yes (mentioned user only, via separate mention notification) |
| Minor field edits | No |

The note author is never notified about their own notes.

## Notification batching

Rapid activity on the same ticket is grouped into a single digest notification rather than flooding watchers with individual emails.

**How it works:**
- The first notification for a ticket is sent immediately.
- Subsequent notifications on the same ticket within a **10-minute window** are batched together.
- At the end of the window, a single digest email is sent summarising all activity in the batch.
- The window resets after each digest is sent.

This means a burst of 10 notes in 5 minutes produces one email, not ten.

## Muted watchers and active viewers

If you are currently viewing a ticket (presence is active), or if you have muted the ticket's notifications, you receive **database-only notifications** — the notification bell updates but no email is sent.

## @mentions

Type `@` in a note to mention a specific user. Mentioned users receive a notification regardless of whether they are watching the ticket. Mentioned users are excluded from the standard watcher notification for the same note to avoid duplicate alerts.

## Notification bell

The notification bell (top navigation bar) shows an unread count badge. Click it to open the activity feed. Notifications are marked read when you open the feed.

## Email notifications

Email is sent via the configured `MAIL_*` environment settings. The email includes the note content, ticket subject, and a direct link to the ticket.

To disable email for a specific ticket while staying a watcher, mute the ticket (the mute control is available on the ticket watcher panel).

---

## See also

- [Note Signals](note-signals.md) — @mentions and what triggers watcher notifications
- [Installation](installation.md) — configuring `MAIL_*` settings and the queue worker
