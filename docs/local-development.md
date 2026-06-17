# Local Setup and Development

This repository contains two applications:

- `apps/backend` — Laravel API
- `apps/web` — Vite + React web client

There is no workspace runner at the repository root. Install dependencies and run commands from each app folder.

> **Mobile app:** There is no `apps/mobile` directory in this repository. Any older references to Expo or React Native do not apply.

## Related documentation

| Document | Purpose |
|----------|---------|
| [Testing](TESTING.md) | Test, lint, and validation commands |
| [Deployment](DEPLOYMENT.md) | Deploying backend and web |
| [API guides](api/README.md) | Narrative API documentation |
| [Backend app](../apps/backend/README.md) | API setup and architecture |
| [Web app](../apps/web/README.md) | Frontend portals and routes |

## Prerequisites

- PHP **8.3+**
- Composer
- Node.js and npm

## First-Time Setup

Start from the repository root.

### Backend

Use Composer for PHP/Laravel dependencies. Use npm only for the backend's Vite frontend assets.

```bash
cd apps/backend
composer i
```

If this is a fresh local Laravel setup, create your environment file and generate an app key:

```bash
cp .env.example .env
php artisan key:generate
```

Install backend Node dependencies when you need to build or run Vite assets for the Laravel app:

```bash
npm i
```

Run migrations and seed local fixtures:

```bash
php artisan migrate:fresh --seed
```

### Web App

```bash
cd apps/web
npm i
cp .env.example .env
```

Ensure `VITE_API_BASE_URL=http://127.0.0.1:8001` in `apps/web/.env` so the client talks to the local API.

## Running Locally

Open a separate terminal for each long-running process.

### Backend API

```bash
cd apps/backend
php -S 127.0.0.1:8001 -t public
```

The API is available at:

```text
http://127.0.0.1:8001
```

If you are working on Laravel Vite assets, run Vite in another backend terminal:

```bash
cd apps/backend
npm run dev
```

### Web App

```bash
cd apps/web
npm run dev
```

Vite prints the local URL (typically `http://localhost:5173`).

## When to Use `composer i` vs `npm i`

Use `composer i` in `apps/backend` when:

- Setting up the Laravel backend for the first time.
- `apps/backend/composer.json` or `apps/backend/composer.lock` changes.
- PHP dependencies are missing from `apps/backend/vendor`.

Use `npm i` in an app folder when:

- Setting up `apps/web` or backend Vite assets for the first time.
- That app's `package.json` or lockfile changes.
- Node dependencies are missing from that app's `node_modules`.

Because each app has its own dependency files, run `npm i` inside the specific app you are working on, not from the repository root.

## Daily Development Workflow

1. Pull the latest code.
2. Check which app changed.
3. Run dependency installs only where needed:
   - Backend PHP changes: `cd apps/backend && composer i`
   - Backend Vite changes: `cd apps/backend && npm i`
   - Web changes: `cd apps/web && npm i`
4. Start the backend if your work needs the API:

   ```bash
   cd apps/backend
   php -S 127.0.0.1:8001 -t public
   ```

5. Start the web app from `apps/web` when working on the client.
6. Keep each long-running process in its own terminal.

## Useful Commands

### Backend

```bash
cd apps/backend
php artisan migrate:fresh --seed
php artisan test
npm run build
```

After the department schema squash, use `migrate:fresh --seed` rather than incremental `migrate` when resetting local data. There is no upgrade path from databases that still had legacy enum columns on `issues`, `categories`, or `managers`, or singular `district_id` on `officers` / `managers`.

### Web

```bash
cd apps/web
npm run build
npm run lint
npm run preview
```

## Backend Department Fixtures

When you run `php artisan migrate:fresh --seed` in `apps/backend`, local seeders create canonical rows in the backend `departments` table and assign demo actors to them.

- Managers have one or more departments through `department_ids` / the `department_manager` pivot.
- Officers have one or more departments through `department_ids` / the `department_officer` pivot.
- Seeded active departments include IDs `1` and `2`; reach them without authentication via `GET /api/departments` or `GET /api/departments/{id}` (active-only for unauthenticated callers; rate-limited to 30 requests per minute). Use the Postman **Departments / List Departments** request (no token) to inspect local department IDs before creating managers or registering officers.
- Department deletion is blocked while a department is assigned to any manager or officer.
- Issue departments are derived from the selected category on create and whenever `category_id` changes on update. They are stored only in the `department_issue` pivot (the `issues.department` enum column was removed). API responses expose a read-only `departments` array; clients must not send `department` on issue create or update. Issue list filtering uses query param `department` with a department code (pivot any-match).
- Active main managers can replace officer or manager department pivots with `PATCH /api/officers/{officer}/departments` and `PATCH /api/managers/{manager}/departments`, body `{"department_ids":[1,2]}` or `[]` to clear all assignments. Non-main managers receive `403 Forbidden`. Use the Postman **Managers** folder requests with a main manager token.

## Backend District Fixtures

Local seeders also create canonical rows in the backend `districts` table and assign demo managers/officers through many-to-many pivots.

- Managers have zero or more districts through `district_ids` / the `district_manager` pivot.
- Officers have zero or more districts through `district_ids` / the `district_officer` pivot.
- Auth profile payloads for managers and officers return `districts` arrays of compact objects (`id`, `name`, `postal_prefix`), not a singular actor-side `district_id`.
- Use the Postman **Districts / List Districts** request to inspect local district IDs before creating managers, registering officers, or updating actor district assignments.
- Active managers and active officers can replace their own districts with `PATCH /api/auth/me/districts` and `{"district_ids":[1,2]}`. Active managers can replace an officer's districts with `PATCH /api/officers/{officer}/districts`. Regular users receive `403 Forbidden` from the self-service district endpoint because they do not have district assignments.
- Only active managers can create, update, or delete district records. District deletion is blocked while the district is assigned to any manager, assigned to any officer, or referenced by issues.
- Issue district handling is intentionally unchanged: `issues.district_id` remains a singular issue location/reference field and is out of scope for actor district many-to-many assignments.
