# AGENTS.md

Repo guide for coding agents. App is a Laravel ticket tracker (Blade plus Bootstrap, Alpine, SQLite/MySQL/PG). PRs target `2.0.0`; `master` is the legacy default branch.

## Commands

- `composer test` (full PHP suite, uses its own test DB)
- `php artisan test <path>` (single file)
- `vendor/bin/pint --test <paths>` (lint, must pass)
- `npm run build` (verify after touching `resources/`)

## Git rules

- One issue, one branch (`fix/issue-N-slug`), one PR targeting `2.0.0`. Never commit to primary.
- No commit prefixes (`feat:`, `fix:`, etc). Subject: capitalized imperative, max 50 chars, no trailing period. Blank line, body wrapped at 72 cols, what plus why.
- No em-dashes or en-dashes anywhere.

## Where logic lives

- Query rules: Eloquent scopes on models (`Ticket::scopeFilter`, `scopeOpen`, `scopeClosed`, `scopeWithListRelations`, `LIST_SORTABLE`). No new Service classes for query logic.
- Auth: policies (`app/Policies`), validation: Form Requests (`app/Http/Requests`).
- Services stay thin and single-purpose (`app/Services`). MCP tools live in `app/Mcp/Tools`, registered in `app/Mcp/Servers/TicketsServer.php`.
- Shared Blade bits: `resources/views/components`, `resources/views/partials`.

## Tests and data

- `tests/Feature` (HTTP, MCP via `TicketsServer::actingAs`), `tests/Unit` (models, services, requests). `SeedsDatabase` trait migrates plus seeds per test.
- Factories in `database/factories`; `DefaultsSeeder` plus `UserSeeder` define baseline data (admin `password123` in seeds only, never valid in any deployed environment).
- Reproduce with a failing test first. Keep HTTP/feature tests to rendered assertions, never source greps.

## Docs

- `docs/installation.md` (setup), `docs/api.md`, `docs/mcp.md`, `llms.txt` (agent API guide). Update docs in the same PR as the code change.
