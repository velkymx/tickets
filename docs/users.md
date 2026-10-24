# User Administration

## User roles

| Role | Flag | Access |
|------|------|--------|
| Standard user | `admin = false` | Tickets, KB (based on KB role), own profile |
| Administrator | `admin = true` | Everything, including Admin menu and Automations |

The `admin` flag is set directly in the database - there is no UI to promote users to admin after seeding. Use a database client or `php artisan tinker`:

```php
App\Models\User::where('email', 'user@example.com')->update(['admin' => true]);
```

## Deactivating a user

Admins can set a user inactive under **Admin → Users → Edit** (`active = false`). Deactivation blocks every access path immediately:

- Password and SSO login are refused.
- Existing web sessions end on the next request and the user is redirected to the login page.
- The user's API token is rejected on REST and MCP HTTP calls, and `MCP_USER_ID` no longer resolves for stdio MCP servers.

Reactivating the user (`active = true`) restores access with the same token.

## Knowledge Base roles

KB access is controlled separately from the admin flag:

| KB Role | Can do |
|---------|--------|
| *(none)* | Read public articles only |
| `author` | Create and edit KB articles |
| `admin` | All author actions + manage categories, tags, and trash |

Set KB role from a database client or tinker:

```php
App\Models\User::where('email', 'user@example.com')->update(['kb_role' => 'author']);
```

An administrator (`admin = true`) automatically has KB admin access regardless of `kb_role`.

## Profile fields

Users manage their own profiles. Navigate to **Profile → Edit Profile** (top-right user menu).

| Field | Notes |
|-------|-------|
| Name | Display name across the app |
| Email | Used for login and notifications |
| Phone | Optional, displayed on profile |
| Title | Job title, displayed on profile |
| Bio | Free text, displayed on profile |
| Timezone | Used for local time display on profile |
| Theme | `Automatic` (OS), `Default` (light), `Darkly` (dark) |
| Avatar | Uploaded image; falls back to Gravatar if not set |

## Notification preferences

**Profile → Edit Profile** ends with notification controls, all on by default: per-category toggles (Mentions, Replies, Watched tickets), an Email master switch, and Email timing (Digest or Immediately). Turning a category off keeps it in-app only; turning Email off keeps everything in-app only. Immediately sends mail at once, Digest keeps the batched summary. Preferences live in one JSON column with defaults merged on read, so untouched users see no change. The digest job re-checks preferences at send time, so opting out after a digest is scheduled still excludes those entries. Omitted fields never reset an explicit opt-out. A muted ticket never emails.

## Avatar

Avatars are uploaded from the **Edit Profile** page. Supported formats: JPEG, PNG, GIF, WebP. Stored in `storage/app/public/images/avatars/`. Falls back to the user's Gravatar (based on email) if no avatar is uploaded.

## API tokens

Each user has at most one API token at a time.

**Generate:**
1. Navigate to **Profile → Edit Profile**.
2. Scroll to **API Token**.
3. Click **Generate Token**.
4. Copy the token - it is shown only once.

**Revoke:**
1. Navigate to **Profile → Edit Profile → API Token**.
2. Click **Revoke Token**.

Revoking immediately invalidates the existing token.

## Password

Users change their own password on the **Edit Profile** page. Locked-out users
reset it themselves from the login page ("Forgot your password?"): the link
request never reveals whether an email exists, tokens expire after 60 minutes
and are single-use. If mail is not configured, use `php artisan tinker`:

```php
App\Models\User::where('email', 'user@example.com')
    ->update(['password' => bcrypt('new-password')]);
```

## Single sign-on

Google, GitHub, GitLab (including self-hosted via `GITLAB_HOST`), and
Microsoft Entra logins are available when their credentials are set in
`config/services.php` (`*_CLIENT_ID`, `*_CLIENT_SECRET`, `*_REDIRECT_URI`).
Unconfigured providers stay off the login page entirely. First-time SSO users
are created only when an allow-list permits it (`SSO_ALLOWED_DOMAINS`,
`AZURE_TENANT_ID`, `GITHUB_ALLOWED_ORG`, `GITLAB_ALLOWED_GROUP`); otherwise the
callback refuses with no account created. Email linking requires a verified
provider email. Deactivated users are blocked on every path. Set
`PASSWORD_LOGIN_ENABLED=false` or `REGISTRATION_ENABLED=false` for SSO-only
installs.

## The `unassigned` system account

The `UserSeeder` creates an `unassigned` account (no password) used as a placeholder when no assignee is set on a ticket. Do not log in as this user or assign it a password.

---

## See also

- [KB Admin](kb-admin.md) - managing KB categories, tags, and article visibility
- [API](api.md) - token generation and revocation
