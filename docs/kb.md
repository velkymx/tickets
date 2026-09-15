# Knowledge Base — MVP Design Spec

**Date:** 2026-03-23
**Approach:** Hybrid — Standalone module with shared components
**Status:** Approved

---

## Overview

A Knowledge Base module for the ticket management system that serves as the single source of truth for all project documentation — architectural schemas, module dependencies, code style guides, cheat sheets, and ideas.

The KB is a standalone module within the Laravel app. It has its own models, controllers, services, and views, but shares key infrastructure with the ticket system: the MarkdownService, a new shared AttachmentService, a new CrossReferenceService for cross-linking between KB articles and tickets, and a SearchService abstraction designed for future Scout integration.

**Audience:** Internal team members (authors/editors) and a broader audience including stakeholders, new hires, and unauthenticated external readers for public articles.

---

## Data Model

### kb_categories

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string, unique | |
| description | text, nullable | |
| sort_order | integer, default 0 | |
| timestamps | | |

### kb_tags

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string, unique | |
| timestamps | | |

### kb_articles

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| title | string | |
| slug | string, unique | |
| body_markdown | longtext | |
| body_html | longtext | Pre-rendered on save |
| category_id | FK -> kb_categories | |
| user_id | FK -> users | Creator |
| owner_id | FK -> users | Current owner (may differ from creator) |
| status | enum: draft, verified, deprecated | Default: draft |
| visibility | enum: public, internal, restricted | Default: internal |
| reviewed_at | timestamp, nullable | Manually set by owner |
| published_at | timestamp, nullable | |
| timestamps | | |
| soft deletes | | |

**Foreign key cascade policy:** All KB foreign keys use `onDelete('cascade')`. When an article is force-deleted, its versions, permissions, attachments, and tag pivots are cascaded. Soft deletes are the default — force-delete is an admin-only database operation, not exposed in the UI.

### kb_article_tag (pivot)

| Column | Type | Notes |
|--------|------|-------|
| article_id | FK -> kb_articles | |
| tag_id | FK -> kb_tags | |

### kb_article_versions

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| article_id | FK -> kb_articles | |
| user_id | FK -> users | Who made the change |
| title | string | Snapshot |
| body_markdown | longtext | Full snapshot (not delta) |
| body_html | longtext | Full snapshot |
| commit_message | string | Required, git-style |
| version_number | integer | Per-article increment. Uses `UNIQUE(article_id, version_number)` constraint; computed via `MAX(version_number) + 1` with `lockForUpdate()` to prevent race conditions. |
| timestamps | | |

### kb_article_permissions

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| article_id | FK -> kb_articles | |
| user_id | FK -> users | Grants access for restricted articles |
| timestamps | | |

### kb_article_attachments

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| article_id | FK -> kb_articles | |
| user_id | FK -> users | |
| filename | string | |
| path | string | |
| mime_type | string | |
| size | unsignedBigInteger | |
| timestamps | | |

### users table (migration)

| Column | Type | Notes |
|--------|------|-------|
| kb_role | enum: author, admin, nullable | Default: null (reader) |

---

## Shared Components

### MarkdownService (existing, no changes)

Already a standalone service. Both tickets and KB call it directly for Markdown-to-HTML rendering.

### AttachmentService (new, extracted)

Extract file upload/storage logic from the current note attachment handling into a shared service. Both `NoteAttachment` and `KbArticleAttachment` use it.

```php
class AttachmentService
{
    public function store(UploadedFile $file, string $directory): AttachmentData;
    public function delete(string $path): void;
}
```

Existing note attachment code is refactored to use this service.

### CrossReferenceService (new)

A dedicated service for resolving cross-references at render time. Integrated into the MarkdownService rendering pipeline (not MentionService, which is specific to @mention DB records).

- In KB article body: `#123` becomes a link to ticket 123
- In ticket notes: `kb:some-slug` becomes a link to the KB article
- No foreign keys — purely URL resolution during Markdown-to-HTML rendering
- `MarkdownService` calls `CrossReferenceService::resolve($html)` as a post-processing step
- Matching patterns: ticket refs use `\b#(\d+)\b`, KB refs use `\bkb:([a-z0-9-]+)\b` — both patterns avoid matching inside URLs and code blocks

### SearchService (new abstraction)

```php
interface SearchableRepository
{
    public function search(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator;
}
```

`KbSearchService` implements this using database full-text queries. When Scout is added later, a `ScoutKbSearchService` replaces it — same interface, no controller changes.

**Refactoring scope:** Two new services (AttachmentService, CrossReferenceService), one new interface (SearchableRepository), one minor MarkdownService update (call CrossReferenceService in render pipeline).

**Service provider binding:** Register `SearchableRepository -> KbSearchService` in `AppServiceProvider` (or a dedicated `KbServiceProvider`).

---

## Routes & Controllers

### Web Routes (prefix: `/kb`)

**Route ordering:** Static routes (`create`, `search`, `category/*`, `tag/*`) MUST be registered before the wildcard `{slug}` route. A route constraint on `{slug}` excludes reserved words: `create`, `category`, `tag`, `search`, `admin`, `trashed`.

**Middleware groups:**
- **Public group** (index, show, search, category, tag): `web` only — no `auth` middleware. Auth is optional; the controller/policy checks `auth()->user()` to determine access.
- **Author group** (create, store, edit, update, attachments): `web`, `auth`
- **Admin group** (`/kb/admin/*`): `web`, `auth`, plus policy check for admin role

| Route | Controller Method | Access |
|-------|------------------|--------|
| `GET /kb` | `KbController@index` | Public (lists public + user's accessible articles) |
| `GET /kb/create` | `KbController@create` | Author/Admin |
| `POST /kb` | `KbController@store` | Author/Admin |
| `GET /kb/search` | `KbController@search` | Public |
| `GET /kb/category/{slug}` | `KbController@category` | Public |
| `GET /kb/tag/{slug}` | `KbController@tag` | Public |
| `GET /kb/{slug}` | `KbController@show` | Visibility-dependent |
| `GET /kb/{slug}/edit` | `KbController@edit` | Author/Admin + owner/permission |
| `PUT /kb/{slug}` | `KbController@update` | Author/Admin + owner/permission |
| `DELETE /kb/{slug}` | `KbController@destroy` | Admin only |
| `GET /kb/{slug}/history` | `KbVersionController@index` | Same as show |
| `GET /kb/{slug}/history/{version}` | `KbVersionController@show` | Same as show |
| `GET /kb/{slug}/diff/{from}/{to}` | `KbVersionController@diff` | Same as show |
| `POST /kb/{slug}/restore/{version}` | `KbVersionController@restore` | Author/Admin |
| `POST /kb/{slug}/attachments` | `KbController@uploadAttachment` | Author/Admin + owner/permission |
| `DELETE /kb/{slug}/attachments/{id}` | `KbController@deleteAttachment` | Author/Admin + owner/permission |

**Attachment constraints:** Max file size 10MB. Allowed MIME types: images (jpg, png, gif, svg, webp), documents (pdf, md, txt, csv), archives (zip). Max 20 attachments per article. Upload routes use `throttle:uploads` rate limiter.

#### Admin Routes (prefix: `/kb/admin`)

| Route | Controller Method | Access |
|-------|------------------|--------|
| `GET /kb/admin/categories` | `KbAdminController@categories` | Admin |
| `POST /kb/admin/categories` | `KbAdminController@storeCategory` | Admin |
| `PUT /kb/admin/categories/{id}` | `KbAdminController@updateCategory` | Admin |
| `DELETE /kb/admin/categories/{id}` | `KbAdminController@destroyCategory` | Admin |
| `GET /kb/admin/tags` | `KbAdminController@tags` | Admin |
| `POST /kb/admin/tags` | `KbAdminController@storeTag` | Admin |
| `PUT /kb/admin/tags/{id}` | `KbAdminController@updateTag` | Admin |
| `DELETE /kb/admin/tags/{id}` | `KbAdminController@destroyTag` | Admin |
| `GET /kb/admin/trashed` | `KbAdminController@trashed` | Admin |
| `POST /kb/admin/trashed/{id}/restore` | `KbAdminController@restoreArticle` | Admin |

API routes (`/api/v1/kb`) mirror the web routes — added in a later iteration, not MVP.

### Authorization (KbArticlePolicy)

- **view** — Public articles: anyone (policy uses `?User $user` nullable parameter). Internal: authenticated users. Restricted: owner, permitted users, admins. The policy's `before()` method returns `true` for public articles when `$user` is null.
- **create** — Users with Author or Admin KB role.
- **update** — Owner, permitted authors, admins.
- **delete** — Admins only.
- **restore** — Same as update.

### Roles

Add `kb_role` column to `users` table (nullable enum: `author`, `admin`). Null means reader-only. Existing app `admin` flag grants KB admin automatically.

---

## Views & UI

### Layout

KB gets its own sub-layout extending `layouts/app.blade.php` — adds a sidebar for navigation while keeping the existing navbar.

### Sidebar

- Category list (collapsible, with article counts)
- Tag cloud or tag list
- Quick filters: My Articles, Recently Updated, Needs Review, Drafts
- Status filter (Draft / Verified / Deprecated)

### Article List (`/kb`)

- Card or table layout: title, category, tags, status badge, author avatar, last updated
- Search bar at the top
- Sort by: recently updated, title, status

### Article View (`/kb/{slug}`)

- Full-width reading view with rendered Markdown
- Right sidebar metadata: owner, status badge, category, tags, created/updated dates, last reviewed date, visibility badge
- Action buttons: Edit, View History, Change Owner (admin)
- Breadcrumb: KB > Category > Article Title
- Cross-links to tickets render as styled chips (`#123` becomes a linked badge)

### Editor (`/kb/create`, `/kb/{slug}/edit`)

- Title field
- Markdown editor — reuse existing textarea + preview pattern
- Metadata panel: category dropdown, tag multi-select, status, visibility, owner
- Per-article permissions: user selector (shown when visibility = restricted)
- Save modal: requires a commit message before saving (git commit dialog style)
- Preview toggle for rendered output

### Version History (`/kb/{slug}/history`)

- List of versions: version number, commit message, author, timestamp
- Click to view that version's full content
- Diff view: side-by-side or inline diff between any two versions, GitHub-style (green/red highlighting)

### Shared Alpine.js Components

- Tag input (multi-select with autocomplete)
- Markdown preview toggle
- Save modal with commit message

---

## Search & Discovery

### Database Search (MVP)

- Full-text search against `title`, `body_markdown`, and tag names
- MySQL: `FULLTEXT` index on `kb_articles(title, body_markdown)`
- SQLite (local dev): `LIKE`-based fallback
- Results ranked by relevance, filtered by user's access level

### Discovery Features

- Sidebar navigation by category and tags
- Related articles (shared tags) shown at the bottom of article view
- Recently updated articles on KB index page
- Filter by governance status to surface stale/deprecated content

---

## Governance & Permissions

### Article Lifecycle

- **Draft** — Default on creation. Only visible to the author and admins. Not shown in public/internal listings. On the index page, authenticated users see their own drafts in a separate "My Drafts" section; unauthenticated users never see drafts.
- **Verified** — Published and reviewed. Visible per its visibility setting. Author manually sets `reviewed_at` when marking as verified.
- **Deprecated** — Still accessible via direct link and search, but displayed with a visual warning banner. Excluded from sidebar navigation by default.

**Index listing logic:** Unauthenticated users see only verified public articles. Authenticated users see verified articles matching their access level, plus their own drafts. Admins see all articles regardless of status/visibility.

### Visibility Tiers

- **Public** — No authentication required. Unauthenticated visitors see a minimal public layout (`layouts/kb-public.blade.php`, no app chrome). Authenticated users see the normal app layout.
- **Internal** — Any authenticated user can view.
- **Restricted** — Only the owner, explicitly permitted users (via `kb_article_permissions`), and admins.

### Role Hierarchy

- **Reader** (default, `kb_role = null`) — View articles per visibility rules. Search and filter.
- **Author** (`kb_role = 'author'`) — Create articles, edit own articles, manage own article permissions.
- **Admin** (`kb_role = 'admin'` or existing `admin = true`) — Full control. Edit/delete any article, change ownership, manage categories and tags, view/restore trashed articles.

### Ownership

- Every article has an `owner_id`. Defaults to the creator.
- Ownership can be transferred by the current owner or an admin.
- No automated review reminders in the MVP — owners track review dates manually.

### Required Metadata on Save

Title, category, at least one tag, visibility, commit message. Status defaults to Draft if not set.

---

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Kb/
│   │       ├── KbController.php
│   │       ├── KbVersionController.php
│   │       └── KbAdminController.php       # Category/tag CRUD, trashed articles
│   ├── Requests/
│   │   └── Kb/
│   │       ├── StoreArticleRequest.php
│   │       └── UpdateArticleRequest.php
│   └── Resources/
│       └── KbArticleResource.php
├── Models/
│   ├── KbArticle.php
│   ├── KbCategory.php
│   ├── KbTag.php
│   ├── KbArticleVersion.php
│   ├── KbArticlePermission.php
│   └── KbArticleAttachment.php
├── Policies/
│   └── KbArticlePolicy.php
├── Services/
│   ├── AttachmentService.php          # Shared (extracted)
│   ├── CrossReferenceService.php      # #123 and kb:slug link resolution
│   ├── KbSearchService.php
│   └── KbArticleService.php           # Core business logic (see below)
├── Contracts/
│   └── SearchableRepository.php       # Interface for Scout swap

database/
├── factories/
│   ├── KbArticleFactory.php
│   ├── KbCategoryFactory.php
│   └── KbTagFactory.php
├── migrations/
│   ├── xxxx_create_kb_categories_table.php
│   ├── xxxx_create_kb_tags_table.php
│   ├── xxxx_create_kb_articles_table.php
│   ├── xxxx_create_kb_article_tag_table.php
│   ├── xxxx_create_kb_article_versions_table.php
│   ├── xxxx_create_kb_article_permissions_table.php
│   ├── xxxx_create_kb_article_attachments_table.php
│   └── xxxx_add_kb_role_to_users_table.php
├── seeders/
│   └── KbCategorySeeder.php

resources/views/
├── layouts/
│   └── kb-public.blade.php               # Minimal layout for unauthenticated visitors
├── kb/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── category.blade.php
│   ├── tag.blade.php
│   ├── search.blade.php
│   ├── admin/
│   │   ├── categories.blade.php
│   │   ├── tags.blade.php
│   │   └── trashed.blade.php
│   ├── history/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── diff.blade.php
│   └── partials/
│       ├── sidebar.blade.php
│       ├── article-card.blade.php
│       ├── metadata-panel.blade.php
│       ├── save-modal.blade.php
│       └── version-list.blade.php

routes/
│   # KB routes added to web.php (grouped under /kb prefix)
```

### KbArticleService — Key Methods

```php
class KbArticleService
{
    public function create(array $data, User $author): KbArticle;
    public function update(KbArticle $article, array $data, User $editor, string $commitMessage): KbArticle;
    public function createVersion(KbArticle $article, User $editor, string $commitMessage): KbArticleVersion;  // uses lockForUpdate()
    public function transferOwnership(KbArticle $article, User $newOwner): void;
    public function markReviewed(KbArticle $article): void;
    public function changeStatus(KbArticle $article, string $status): void;
}
```

The `update()` method calls `createVersion()` internally — every update produces a version record. The `createVersion()` method wraps the version_number increment in a `DB::transaction()` with `lockForUpdate()`.

**Note:** The KB is intentionally project-agnostic. Articles are not scoped to a project — the KB serves as a cross-project source of truth. Project-specific context can be conveyed via categories or tags (e.g., a "Project X" category).

### Refactoring Touchpoints

- `NoteAttachment` upload logic → extracted into `AttachmentService`, existing note code updated to use it
- `MarkdownService` → updated to call `CrossReferenceService::resolve()` as a post-processing step in the render pipeline
- `note_attachments.size` → migration to change from `unsignedInteger` to `unsignedBigInteger` for consistency with KB attachments
