# Testing and validation

How to verify RAUW (**Rotterdams Actie Uit de Wijken**) changes locally before opening a pull request. This repository has **no CI pipeline yet** — contributors run the checks below on their machine and document manual steps when automation does not cover a change.

## Overview

| Layer | Automated checks | Manual checks |
|-------|------------------|---------------|
| **Backend API** | PHPUnit, Laravel Pint | Postman collection, API checklists |
| **Web client** | ESLint, production build | Browser smoke tests on active routes |

Related docs:

- [Local development](local-development.md) — prerequisites, ports, migrations
- [Deployment](DEPLOYMENT.md) — production configuration (out of scope for day-to-day testing)
- [Postman collection](postman/README.md) — importable requests and local environment
- [API manual checklists](api/manual-checklists.md) — scenario walkthroughs
- [OpenAPI contract](openapi.yaml) — canonical machine-readable API spec
- [API policies](api-policy.md) — auth tiers, error codes, inactive accounts

## Backend (Laravel API)

Working directory: `apps/backend`.

### PHPUnit

The test suite uses **PHPUnit 12** with an in-memory SQLite database (`phpunit.xml`). Tests do not require a running HTTP server or a seeded `database.sqlite` file.

```bash
cd apps/backend
php artisan test
```

Equivalent via Composer:

```bash
composer test
```

`composer test` clears config cache first, then runs `php artisan test`.

**Layout:** roughly **50** test classes under `tests/`, split into:

| Suite | Directory | Examples |
|-------|-----------|----------|
| **Feature** | `tests/Feature/` | Auth, issues, officer workflows, issue chat, notifications, community posts |
| **Unit** | `tests/Unit/` | Feedback windows, anonymity helpers, notification writers |

Run a single file or filter:

```bash
php artisan test tests/Feature/AuthLoginTest.php
php artisan test --filter=IssueChat
```

### Code style (Laravel Pint)

Pint enforces PHP style. Check without modifying files:

```bash
cd apps/backend
vendor/bin/pint --test
```

Apply fixes locally:

```bash
vendor/bin/pint
```

### Database fixture reset

For manual API exploration or when local data drifts, reset the database and re-seed demo fixtures:

```bash
cd apps/backend
php artisan migrate:fresh --seed
```

This recreates schema and runs seeders (demo users, officers, managers, Rotterdam hubs/wijken, categories). After reset, start the API:

```bash
php -S 127.0.0.1:8001 -t public
```

Canonical local port is **8001** (see [local development](local-development.md)). Do not use port 8000 in new docs or env examples.

> After schema squash migrations, prefer `migrate:fresh --seed` over incremental `migrate` when resetting local data.

### What PHPUnit does not cover

- Browser CORS preflight behaviour (test with the web app or Postman from a browser origin)
- Real file upload limits across reverse proxies
- Production cache config (`config:cache`, `route:cache`)
- End-to-end flows across backend + web in one command

Use Postman and the manual checklists for those paths.

## Web client (React + Vite)

Working directory: `apps/web`.

### ESLint

```bash
cd apps/web
npm run lint
```

Runs ESLint across the project (`eslint .`). Fix issues before opening a PR.

### Production build (smoke test)

A successful build confirms TypeScript/JSX, Vite bundling, and Tailwind compile:

```bash
cd apps/web
npm run build
```

Output lands in `apps/web/dist/`. Optionally preview the static build:

```bash
npm run preview
```

Set `VITE_API_BASE_URL` in `apps/web/.env` before building if you need the bundle to target a non-default API host (see [Deployment](DEPLOYMENT.md)).

### Browser smoke tests

With backend and web dev servers running (see [local development](local-development.md)):

1. **User portal** — register/login, map, feed, report (`/meld`), community news (`/nieuws`), account/settings.
2. **Officer portal** — officer login (with coordinates), command center (`/meldingen`), reports (`/rapport`), sector/service profile routes.

**Issue chat (`/chat`):** The product includes user and officer chat screens (`U_Chat.jsx`, `H_Chat.jsx`), but the `/chat` route may still be commented out in `App.jsx` during active development. **Only test chat in the browser when `/chat` is enabled** in the router — do not uncomment routes solely for documentation work. When enabled, verify list/open/send flows against the [Issue Chat API](api/issue-chat.md).

**Not in scope:** manager web UI (`src/manager/` has no active routes).

## Manual API testing

### Postman

Import the collection and local environment from `docs/postman/`:

- `rauw-backend.postman_collection.json`
- `rauw-local.postman_environment.json`

Full setup, demo credentials, token variables, and recommended request order: [Postman README](postman/README.md).

Postman is not subject to CORS. Use it to validate auth, issues, officer Tier B/C behaviour, attachments, and notifications without the web client.

### API checklists

Structured manual scenarios (duplicate issues, officer shift, feedback windows, etc.) live in [docs/api/manual-checklists.md](api/manual-checklists.md). Use them after automated tests pass when you change cross-cutting API behaviour.

## API contract: OpenAPI vs Scribe

| Source | Role |
|--------|------|
| **`docs/openapi.yaml`** | **Canonical** API contract (paths, schemas, error shapes). Update this first when the API changes. |
| **`docs/openapi/schemas.yaml`** | Shared component schemas referenced by the main spec. |
| **`docs/api/*.md`** | Narrative domain guides; must stay consistent with OpenAPI. |
| **Scribe** (`knuckleswtf/scribe` in dev dependencies) | Optional HTML mirror generated from Laravel routes/annotations. Not the source of truth. |

Workflow when changing endpoints:

1. Implement and add/update **PHPUnit** coverage.
2. Update **`docs/openapi.yaml`** (and `schemas.yaml` if needed).
3. Update narrative **`docs/api/`** guides and the **Postman** collection if request shapes changed.
4. Optionally regenerate Scribe for local browsing (`php artisan scribe:generate` from `apps/backend`) — do not treat Scribe output as canonical.

Cross-cutting behaviour (Sanctum, inactive accounts, officer tiers, structured error `code` values) is documented in [api-policy.md](api-policy.md).

## Pre-PR checklist

Before opening a pull request:

- [ ] `cd apps/backend && php artisan test` — all tests green
- [ ] `cd apps/backend && vendor/bin/pint --test` — PHP style clean (or run `pint` to fix)
- [ ] `cd apps/web && npm run lint` — no ESLint errors
- [ ] `cd apps/web && npm run build` — production build succeeds
- [ ] If API behaviour changed: OpenAPI updated; Postman and/or manual checklist steps exercised
- [ ] If database schema or seeders changed: `php artisan migrate:fresh --seed` verified locally
- [ ] No secrets committed (`.env`, tokens, production credentials)
- [ ] Manual smoke test on routes you touched (web and/or Postman)

## Related documentation

| Document | Purpose |
|----------|---------|
| [Local development](local-development.md) | Daily dev workflow |
| [Deployment](DEPLOYMENT.md) | Production deploy shape |
| [Backend app README](../apps/backend/README.md) | API service entry point |
| [Web app README](../apps/web/README.md) | Frontend portals and routes |
| [API guides](api/README.md) | Domain documentation index |
