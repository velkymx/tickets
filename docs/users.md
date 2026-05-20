# User Administration

## User roles

| Role | Flag | Access |
|------|------|--------|
| Standard user | `admin = false` | Tickets, KB (based on KB role), own profile |
| Administrator | `admin = true` | Everything, including Admin menu and Automations |

The `admin` flag is set directly in the database — there is no UI to promote users to admin after seeding. Use a database client or `php artisan tinker`:

```php
App\Models\User::where('email', 'user@example.com')->update(['admin' => true]);
```

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

## Avatar

Avatars are uploaded from the **Edit Profile** page. Supported formats: JPEG, PNG, GIF, WebP. Stored in `storage/app/public/images/avatars/`. Falls back to the user's Gravatar (based on email) if no avatar is uploaded.

## API tokens

Each user has at most one API token at a time.

**Generate:**
1. Navigate to **Profile → Edit Profile**.
2. Scroll to **API Token**.
3. Click **Generate Token**.
4. Copy the token — it is shown only once.

**Revoke:**
1. Navigate to **Profile → Edit Profile → API Token**.
2. Click **Revoke Token**.

Revoking immediately invalidates the existing token.

## Password

Users change their own password on the **Edit Profile** page. There is no admin password-reset UI — use `php artisan tinker` if a user is locked out:

```php
App\Models\User::where('email', 'user@example.com')
    ->update(['password' => bcrypt('new-password')]);
```

## The `unassigned` system account

The `UserSeeder` creates an `unassigned` account (no password) used as a placeholder when no assignee is set on a ticket. Do not log in as this user or assign it a password.

---

## See also

- [KB Admin](kb-admin.md) — managing KB categories, tags, and article visibility
- [API](api.md) — token generation and revocation
