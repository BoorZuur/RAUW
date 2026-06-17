# Deployment

High-level guide for deploying **RAUW** (**Rotterdams Actie Uit de Wijken**) to production. The monorepo ships two independent deploy units:

| Unit | Artifact | Typical hosting |
|------|----------|-----------------|
| **Backend API** | Laravel PHP application (`apps/backend`) | VPS or managed PHP runtime (nginx/Apache + PHP-FPM) |
| **Web client** | Static files from Vite build (`apps/web/dist/`) | Static file host, object storage + CDN, or same reverse proxy as `index.html` |

This document describes a **generic** deployment shape. It does not prescribe a specific cloud vendor. For Dutch OWASP notes and a compact install/deploy summary, see [backend OWASP & deploy notes](backend-owasp-installatie-deployment.txt).

**Not in scope:** mobile apps, manager web UI (manager actors use the API only).

**Local development port:** canonical API URL during development is `http://127.0.0.1:8001` via `php -S 127.0.0.1:8001 -t public` (see [local development](local-development.md)). The OWASP notes file mentions `php artisan serve --port=8001`; either works locally — prefer the built-in PHP server command above for consistency with Postman and web env examples.

## Architecture overview

```
┌─────────────────┐     HTTPS + Bearer token      ┌─────────────────┐
│  Web (static)   │ ────────────────────────────► │  Laravel API    │
│  apps/web/dist  │     CORS: allowed origins     │  apps/backend   │
└─────────────────┘                               └────────┬────────┘
                                                           │
                                                           ▼
                                                  ┌─────────────────┐
                                                  │  Database       │
                                                  │  (MySQL/PG/…)   │
                                                  └─────────────────┘
                                                           │
                                                           ▼
                                                  ┌─────────────────┐
                                                  │  Local disk     │
                                                  │  (uploads)      │
                                                  └─────────────────┘
```

The web app is a single-page application. All API calls go to the backend base URL configured at **build time** (`VITE_API_BASE_URL`). Sanctum personal access tokens are sent as `Authorization: Bearer {token}`; CORS must allow the deployed web origin.

## Backend deployment

Working directory on the server: `apps/backend` (or the deployed copy of that tree).

### 1. Install dependencies

Production install (no dev packages):

```bash
composer install --no-dev --optimize-autoloader
```

PHP **8.3+** and Laravel **13** are required (see `composer.json`).

### 2. Environment configuration

Copy `.env.example` to `.env` on the server and set production values. Minimum set:

| Variable | Production value | Notes |
|----------|------------------|-------|
| `APP_ENV` | `production` | Never `local` in production |
| `APP_DEBUG` | `false` | **Required** — prevents stack traces and SQL leaking to API clients |
| `APP_KEY` | Generated secret | `php artisan key:generate` on first deploy |
| `APP_URL` | Public API base URL | e.g. `https://api.example.com` |
| `DB_CONNECTION` | `mysql`, `pgsql`, etc. | SQLite is for local dev only |
| `DB_*` | Database credentials | Host, database name, user, password |
| `FRONTEND_URL` | Staging or primary web origin | CORS allowed origin (no wildcard) |
| `FRONTEND_URL_PRODUCTION` | Production web origin | Second allowed origin when staging and prod differ |
| `SANCTUM_TOKEN_EXPIRATION` | `10080` (default) | Token lifetime in minutes (7 days) |
| `FILESYSTEM_DISK` | `local` (default) | Upload storage driver |
| `QUEUE_CONNECTION` | See [Queues & mail](#queues--mail) | Default in repo is `database` |
| `MAIL_MAILER` | See [Queues & mail](#queues--mail) | Default in repo is `log` |

Reference: [`apps/backend/.env.example`](../apps/backend/.env.example) (read-only template in repo). Web build env: [`apps/web/.env.example`](../apps/web/.env.example).

### 3. Database migrations

Run migrations on deploy (non-interactive):

```bash
php artisan migrate --force
```

Do **not** run `migrate:fresh` in production — it drops all data.

### 4. Optimize Laravel

After env is correct:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Clear caches when env changes:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 5. Storage and permissions

- Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process user.
- Run `php artisan storage:link` if you serve user-visible files from `public/storage` (the API serves most attachments via **authenticated download routes**, not public URLs).
- Uploaded issue, chat, resolution, and community post files live on the configured disk (`FILESYSTEM_DISK=local` by default). Plan disk backups and capacity; attachments are **not** world-readable assets.

### 6. Web server

Point the document root at `apps/backend/public`. Route all requests through `public/index.php` (standard Laravel). Terminate TLS at the reverse proxy or load balancer.

Health: Laravel exposes `GET /up` for process health (not part of the JSON `/api` contract in OpenAPI).

### 7. Operational processes

If you enable queued jobs or the scheduler:

- **Queue worker:** `php artisan queue:work` (or a process manager equivalent) when `QUEUE_CONNECTION` is not `sync`.
- **Scheduler:** cron entry `* * * * * php /path/to/artisan schedule:run`

The repository defaults (`QUEUE_CONNECTION=database`, `MAIL_MAILER=log`) suit local development; production mail and queue topology is **TBD** unless your team configures SMTP and workers explicitly.

## Web deployment

Working directory: `apps/web`.

### 1. Configure API URL (build time)

Vite embeds env vars at **build** time. Set the production API base URL before building:

```bash
# apps/web/.env (or CI env)
VITE_API_BASE_URL=https://api.example.com
```

This must match the deployed backend `APP_URL` scheme and host (HTTPS in production).

### 2. Build static assets

```bash
cd apps/web
npm ci
npm run build
```

Deploy the contents of `dist/` to your static host. Configure the server so client-side routes fall back to `index.html` (SPA).

### 3. Smoke test after deploy

- Load the site over **HTTPS**.
- Login as user and officer (officer login requires GPS coordinates in the API).
- Confirm API calls hit the production host (browser network tab).
- If CORS errors appear, fix `FRONTEND_URL` / `FRONTEND_URL_PRODUCTION` on the backend (see below).

> **Known debt:** some `apps/web/src/services/*` modules still hardcode `localhost:8001`. New code should use `import.meta.env.VITE_API_BASE_URL`. A full refactor is out of scope for this doc.

## Sanctum and CORS

Authentication uses **Laravel Sanctum personal access tokens** (Bearer), not cookie-based SPA auth. See [API policies](api-policy.md).

CORS is explicit — **no wildcard origins**. Configuration: [`apps/backend/config/cors.php`](../apps/backend/config/cors.php) (see also [API policies](api-policy.md)).

| Setting | Behaviour |
|---------|-----------|
| `allowed_origins` | `FRONTEND_URL` + optional `FRONTEND_URL_PRODUCTION` (empty values filtered out) |
| `paths` | `api/*`, `sanctum/csrf-cookie` |
| `supports_credentials` | `false` |

**Rule:** the browser origin of your deployed web app must appear in `FRONTEND_URL` or `FRONTEND_URL_PRODUCTION`. Examples:

| Web URL | Backend env |
|---------|-------------|
| `https://app.example.com` | `FRONTEND_URL_PRODUCTION=https://app.example.com` |
| `https://staging.example.com` + production | `FRONTEND_URL=https://staging.example.com` and `FRONTEND_URL_PRODUCTION=https://app.example.com` |

Postman and server-side clients ignore CORS.

Token lifetime defaults to 7 days (`SANCTUM_TOKEN_EXPIRATION=10080`). Logout revokes the current token only; plan session hygiene for shared devices separately.

## Files and storage

| Concern | Implementation |
|---------|----------------|
| **Default disk** | `local` (`FILESYSTEM_DISK`) |
| **Issue / chat / resolution attachments** | Stored on disk; served via `GET .../attachments/.../download` with Bearer auth |
| **Public URLs** | Attachments are not intended as unauthenticated static assets |
| **Scale-out** | Multiple API nodes require shared storage (NFS, S3-compatible object store) — not configured in the default repo |

Back up upload directories with the database so file metadata and blobs stay consistent.

## Queues and mail

Defaults from `.env.example` (oriented to local development):

| Variable | Default | Production note |
|----------|---------|-----------------|
| `QUEUE_CONNECTION` | `database` | Run queue workers if jobs are dispatched; otherwise jobs sit in the `jobs` table |
| `MAIL_MAILER` | `log` | Writes mail to logs only — configure SMTP or a transactional provider before sending real email |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Set a real sender domain |

Notification and mail behaviour in production should be validated explicitly; the repo does not ship a turnkey mail provider.

## Security checklist

Distilled from [OWASP backend notes](backend-owasp-installatie-deployment.txt) and project policy:

- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `APP_KEY` unique per environment; rotate if compromised
- [ ] HTTPS everywhere (TLS on API and web)
- [ ] Database credentials and `APP_KEY` only in server `.env`, never in git
- [ ] **No secrets in `VITE_*`** — Vite vars are embedded in the client bundle
- [ ] CORS origins match actual web URLs (no `*`)
- [ ] Sanctum token expiration appropriate for your threat model
- [ ] Storage directories not world-readable; attachment downloads stay behind auth
- [ ] Run `composer install --no-dev` in production
- [ ] Keep PHP, Laravel, and dependencies patched (`composer update` on a schedule)
- [ ] Provision the initial main manager through trusted ops — not via public demo seeders

Production API errors return generic messages; technical detail belongs in server logs (`LOG_CHANNEL`, `LOG_LEVEL`).

## Deploy order (suggested)

1. Deploy backend with new env, run `migrate --force`, cache config/routes.
2. Verify `GET /up` and a public route (e.g. `GET /api/departments`).
3. Set `VITE_API_BASE_URL` to the live API URL and build the web client.
4. Deploy `dist/` and confirm CORS + login from the browser.
5. Run [testing checklist](TESTING.md) smoke steps against staging before promoting.

## Related documentation

| Document | Purpose |
|----------|---------|
| [Testing](TESTING.md) | Pre-release validation commands |
| [Local development](local-development.md) | Dev ports and workflow |
| [API policies](api-policy.md) | Auth, tiers, errors |
| [OpenAPI](openapi.yaml) | API contract |
| [Postman](postman/README.md) | Manual API verification |
| [Backend OWASP notes](backend-owasp-installatie-deployment.txt) | Dutch OWASP summary + install/deploy bullets |
| [Backend app README](../apps/backend/README.md) | API service overview |
| [Web app README](../apps/web/README.md) | Frontend routes and env |
