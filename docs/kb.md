# Knowledge Base

The Knowledge Base is a standalone module inside the Laravel app. It stores articles in categories, tags them, versions every edit, and serves public, internal, and restricted articles. It shares the Markdown, attachment, cross-reference, and search services with tickets.

## Data model

### kb_categories

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string, unique | |
| description | text, nullable | |
| parent_id | bigint FK nullable, nullOnDelete | Self-reference for nested folders; null means top level |
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
| owner_id | FK -> users | Current owner |
| status | enum: draft, verified, deprecated | Default: draft |
| visibility | enum: public, internal, restricted | Default: internal |
| reviewed_at | timestamp, nullable | Set by the owner |
| published_at | timestamp, nullable | |
| timestamps | | |
| soft deletes | | |

All KB foreign keys use `onDelete('cascade')`. Soft deletes are the default; force-delete is an admin-only database operation.

### kb_article_tag

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
| body_markdown | longtext | Full snapshot |
| body_html | longtext | Full snapshot |
| commit_message | string | Required |
| version_number | integer | Per-article increment with `UNIQUE(article_id, version_number)`; computed under `lockForUpdate()` |
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

### users

| Column | Type | Notes |
|--------|------|-------|
| kb_role | enum: author, admin, nullable | Default: null (reader) |

## Services

### MarkdownService

Renders Markdown to HTML for tickets and KB articles.

### AttachmentService

Stores and deletes uploaded files for note attachments and KB article attachments.

### CrossReferenceService

Resolves cross-references during Markdown rendering:

- `#123` becomes a link to ticket 123.
- `kb:some-slug` becomes a link to the KB article.

The service matches outside URLs and code blocks. It resolves URLs at render time and stores no foreign keys.

### Search

`SearchableRepository` defines the search contract. `KbSearchService` implements it with database `LIKE` queries over `title`, `body_markdown`, and tag names, scoped by `KbArticle::visibleTo`. Results are ordered by `updated_at`.

### KbArticleService

```php
class KbArticleService
{
    public function create(array $data, User $author): KbArticle;
    public function update(KbArticle $article, array $data, User $editor, string $commitMessage): KbArticle;
    public function createVersion(KbArticle $article, User $editor, string $commitMessage): KbArticleVersion;
    public function transferOwnership(KbArticle $article, User $newOwner): void;
    public function markReviewed(KbArticle $article): void;
    public function changeStatus(KbArticle $article, string $status): void;
}
```

`update()` calls `createVersion()` inside a transaction. `createVersion()` increments `version_number` under `lockForUpdate()`.

The KB is project-agnostic. Articles are not scoped to a project; categories and tags carry project context.

## Routes and controllers

### Web routes (prefix: `/kb`)

Static routes are registered before the `{slug}` route. The `{slug}` constraint excludes `create`, `category`, `tag`, `search`, `admin`, and `trashed`.

| Route | Controller method | Access |
|-------|-------------------|--------|
| `GET /kb` | `KbController@index` | Public; lists public and accessible articles |
| `GET /kb/create` | `KbController@create` | Authenticated |
| `POST /kb` | `KbController@store` | Authenticated |
| `GET /kb/search` | `KbController@search` | Public |
| `GET /kb/category/{slug}` | `KbController@category` | Public |
| `GET /kb/tag/{slug}` | `KbController@tag` | Public |
| `GET /kb/{slug}` | `KbController@show` | Visibility-dependent |
| `GET /kb/{slug}/edit` | `KbController@edit` | Owner, permitted user, or KB admin |
| `PUT /kb/{slug}` | `KbController@update` | Owner, permitted user, or KB admin |
| `DELETE /kb/{slug}` | `KbController@destroy` | Admin |
| `GET /kb/{slug}/history` | `KbVersionController@index` | Same as show |
| `GET /kb/{slug}/history/{version}` | `KbVersionController@show` | Same as show |
| `GET /kb/{slug}/diff/{from}/{to}` | `KbVersionController@diff` | Same as show |
| `POST /kb/{slug}/restore/{version}` | `KbVersionController@restore` | KB admin |
| `POST /kb/{slug}/attachments` | `KbController@uploadAttachment` | Owner, permitted user, or KB admin |
| `DELETE /kb/{slug}/attachments/{id}` | `KbController@deleteAttachment` | Owner, permitted user, or KB admin |

### Admin routes (prefix: `/kb/admin`)

| Route | Controller method | Access |
|-------|-------------------|--------|
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

### API routes

The API exposes `GET /api/v1/kb/lookups`, `GET /api/v1/kb/articles`, `POST /api/v1/kb/articles`, `GET /api/v1/kb/articles/{idOrSlug}`, and `PUT/PATCH /api/v1/kb/articles/{id}`. See [REST API](api.md).

## Authorization

`KbArticlePolicy` enforces the following:

- **view** - Public verified articles: anyone, including guests. Internal non-draft articles: authenticated users. Restricted non-draft articles: owner, permitted users, and admins. KB admins pass every ability through `before()`.
- **create** - Any authenticated user.
- **update** - Owner, permitted users, and admins. Permitted users edit content only; visibility, status, and grants stay with the owner or a KB admin. The API and MCP paths enforce the same restriction.
- **delete** - Admins only.
- **restore** - Admins only.

## Roles

- **Reader** (`kb_role = null`) - Views articles per visibility rules, searches, and filters.
- **Author** (`kb_role = 'author'`) - Creates articles, edits own articles, and manages own article permissions.
- **Admin** (`kb_role = 'admin'` or `admin = true`) - Edits and deletes any article, changes ownership, manages categories and tags, and restores trashed articles.

## Article lifecycle

- **Draft** - Default on creation. Visible to the author and admins. Not listed publicly. Authenticated users see their own drafts in a separate "My Drafts" section; guests never see drafts.
- **Verified** - Published and reviewed. Visible per its visibility setting. The author sets `reviewed_at` when marking the article verified.
- **Deprecated** - Accessible by direct link and search, with a warning banner. Excluded from sidebar navigation.

Unauthenticated users see only verified public articles. Authenticated users see verified articles matching their access level plus their own drafts. Admins see all articles.

## Visibility

- **Public** - No authentication required. Guests use `layouts/kb-public.blade.php`; authenticated users use the app layout.
- **Internal** - Any authenticated user.
- **Restricted** - The owner, users in `kb_article_permissions`, and admins.

## Ownership

Every article has an `owner_id`, which defaults to the creator. The current owner or an admin can transfer ownership. Owners set `reviewed_at` manually.

## Required metadata on save

Title, category, at least one tag, visibility, and a commit message. Status defaults to draft.

## Editor

- Title field.
- Markdown editor with preview.
- Category, tags, status, visibility, and owner controls.
- Per-article permissions when visibility is restricted.
- Commit message field.
- Inline validation errors and invalid states on every input.

## Version history

- Version list with version number, commit message, author, and timestamp.
- Full content for each version.
- Diff view between any two versions.

## Attachments

- Maximum 10MB per file.
- Allowed types: jpg, jpeg, png, gif, webp, pdf, md, txt, csv, zip.
- Maximum 20 attachments per article.
- Uploads use the `throttle:uploads` rate limiter.

## Trash

Deleting an article soft-deletes it. Admins restore articles from **Knowledge Base → Admin → Trash**. Permanent deletion is a database operation.

## Search and discovery

- The search bar queries titles, body content, and tag names with `LIKE` and `!` escaping. Results are ordered by last update.
- The MySQL schema has a `FULLTEXT` index on `kb_articles(title, body_markdown)`.
- The sidebar filters by category, tag, status, and ownership.
- Related articles share tags and respect visibility.
- The index lists recently updated articles.
- The status filter surfaces draft, verified, and deprecated content.

## See also

- [REST API](api.md)
- [Knowledge Base Administration](kb-admin.md)
- [Users](users.md)
