# Knowledge Base Administration

KB admin functions require KB admin role (`kb_role = 'admin'` or `admin = true`). Navigate to **Knowledge Base → Admin**.

## Categories

Categories organize articles into sections. Each article belongs to one category.

**Navigate to:** Knowledge Base → Admin → Categories

### Create a category

1. Enter **Name** and optional **Description**.
2. Set **Sort Order** (lower numbers appear first).
3. Optionally pick a **Parent** to nest the category as a folder under it. Leave blank for a top-level category.
4. Click **Create Category**.

The slug is generated automatically from the name. Nested categories render indented in the pills, sidebar, and article pickers. Articles still list under their own category only; there is no rollup into parents, by design.

### Edit a category

Click **Edit** next to the category. Update fields and save. The slug updates to match the new name. To move a category, change its **Parent**; to promote it to top level, clear the parent. A category cannot be its own parent or move under one of its own children, and the same guard runs on every write path, so cycles fail loudly instead of hiding subtrees.

### Delete a category

Click **Delete**. Categories with articles attached cannot be deleted, reassign or delete articles first. Categories with subcategories cannot be deleted either, move or delete the children first. Deleting through any other path promotes orphans to top level (`nullOnDelete`).

## Tags

Tags are free-form labels that can be applied to multiple articles.

**Navigate to:** Knowledge Base → Admin → Tags

### Create a tag

Enter a name and click **Create Tag**. Slug is auto-generated.

### Edit a tag

Click **Edit** next to the tag, update the name, and save.

### Delete a tag

Click **Delete**. Tags can be deleted regardless of whether articles use them - the association is removed.

## Article visibility

Each article has a visibility level set during creation or editing:

| Visibility | Who can read |
|------------|--------------|
| `public` | Everyone, including guests (verified articles) |
| `internal` | All authenticated users (non-draft articles) |
| `restricted` | Owner and explicitly granted users (non-draft articles) |

### Granting restricted access

Restricted access is granted per article through the permitted users field on the create and edit forms. Permitted users can both view and edit article content, and owners retain access without a grant row. Only the owner or a KB admin can change visibility, status, and grants, including through the API and MCP tools.

## Version history

Every save of an article creates a version. To view and restore:

1. Open the article.
2. Click **Version History**.
3. Select two versions to compare - a diff is shown.
4. Click **Restore** on any version to make it the current content.

## Trash (soft deletes)

Deleting an article soft-deletes it - it disappears from the KB but can be recovered.

**Navigate to:** Knowledge Base → Admin → Trash

- Click **Restore** to recover an article.
- Permanently deleted articles are not recoverable from the UI (use a database backup).

## Full-text search

The KB search bar (top of the KB index) searches article titles, body content, and tag names. Results are ordered by last update.

---

## See also

- [Users](users.md) - setting KB roles on users
- [kb.md](kb.md) - author-level KB usage (creating and editing articles)
