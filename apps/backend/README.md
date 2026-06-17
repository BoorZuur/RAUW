# RAUW Backend

Laravel REST API for **Rotterdams Actie Uit de Wijken (RAUW)** — issues, officer workflows, community feed, notifications, and issue chat. For product overview and documentation index, see the [root README](../../README.md).

## Requirements

- PHP 8.3+
- Composer
- SQLite (default; configured in `.env`)

Full local setup (ports, seed accounts, web client) is in [docs/local-development.md](../../docs/local-development.md).

## Setup

From `apps/backend`:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
# or reset demo data:
php artisan migrate:fresh --seed
```

## Environment variables

Key project settings from `.env.example`:

| Variable | Default | Purpose |
|----------|---------|---------|
| `SANCTUM_TOKEN_EXPIRATION` | `10080` | Bearer token lifetime in minutes (7 days) |
| `OFFICER_HUB_ACTIVE_TTL_HOURS` | `10` | Shared officer shift TTL after hub login or start-shift |
| `FRONTEND_URL` | `http://localhost:5173` | CORS allowlist — local SPA origin |
| `FRONTEND_URL_PRODUCTION` | (empty) | CORS allowlist — production SPA origin |
| `DB_CONNECTION` | `sqlite` | Database driver (SQLite for local dev) |
| `APP_DEBUG` | `true` | Set `false` in production so API errors never expose stack traces or SQL |

## Running locally

```bash
php -S 127.0.0.1:8001 -t public
```

API base URL: `http://127.0.0.1:8001`. Health check: `GET /up` (not part of the JSON API contract).

## Common commands

| Command | Purpose |
|---------|---------|
| `php artisan test` | Run PHPUnit feature tests |
| `vendor/bin/pint --test` | Check PHP style (Laravel Pint) |
| `php artisan migrate:fresh --seed` | Reset database with demo fixtures |
| `php artisan scribe:generate` | Optional: regenerate Scribe docs for local browsing — not canonical |

See [docs/TESTING.md](../../docs/TESTING.md) for the full testing guide.

## Architecture overview

- **`app/Http/Controllers/`** — JSON API controllers grouped by domain (auth, issues, community posts, etc.)
- **`app/Models/`** — Eloquent models for users, officers, managers, issues, and related entities
- **`routes/api.php`** — All `/api/*` routes; registration and login are public; everything else requires Sanctum bearer tokens
- **`database/`** — Migrations, factories, and seeders (`migrate:fresh --seed` loads demo data)

**Middleware:** `auth:sanctum` authenticates bearer tokens for all three actor types (user, officer, manager). `actor.active` blocks inactive accounts except on whitelisted profile routes. `officer.hub-active` enforces Tier B (browse without shift) and Tier C (workflow requires active shared shift) for officers. Normative tier rules live in [docs/api-policy.md](../../docs/api-policy.md).

## API documentation

| Resource | Purpose |
|----------|---------|
| [docs/api/README.md](../../docs/api/README.md) | Narrative API index and domain guides |
| [docs/openapi.yaml](../../docs/openapi.yaml) | **Canonical** machine-readable API contract |
| [docs/openapi/schemas.yaml](../../docs/openapi/schemas.yaml) | Shared OpenAPI component schemas |
| [docs/api-policy.md](../../docs/api-policy.md) | Cross-cutting policies (tiers, errors, GPS trust) |
| [docs/DATABASE.md](../../docs/DATABASE.md) | Database ERD diagram and DBML index |
| [docs/DBML.txt](../../docs/DBML.txt) | Editable DBML source (dbdiagram.io) |
| [docs/postman/](../../docs/postman/README.md) | Postman collection and local environment |

Scribe (`GET /docs.openapi` after `php artisan scribe:generate`) is an optional local mirror — treat OpenAPI and narrative docs as primary.

## Further reading

- [docs/local-development.md](../../docs/local-development.md) — monorepo local setup
- [docs/DEPLOYMENT.md](../../docs/DEPLOYMENT.md) — deployment overview
- [docs/OWASP.md](../../docs/OWASP.md) — OWASP Top 10 coverage and security edge cases
