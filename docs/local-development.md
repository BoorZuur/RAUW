# Local Setup and Development

This repository is split into separate apps:

- `apps/backend` — Laravel backend
- `apps/web` — Vite React web app
- `apps/mobile` — Expo React Native mobile app

There is no workspace runner at the repository root. Install dependencies and run commands from each app folder.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js and npm
- Expo tooling for mobile development, usually through `npx expo ...`

## First-Time Setup

Start from the repository root:

```bash
cd C:\Users\henk-\Development\RAUW
```

### Backend

Use Composer for PHP/Laravel dependencies. Use npm only for the backend's Vite frontend assets.

```bash
cd apps/backend
composer i
```

If this is a fresh local Laravel setup, create your environment file and generate an app key:

```bash
copy .env.example .env
php artisan key:generate
```

Install backend Node dependencies when you need to build or run Vite assets for the Laravel app:

```bash
npm i
```

### Web App

```bash
cd C:\Users\henk-\Development\RAUW\apps\web
npm i
```

### Mobile App

```bash
cd C:\Users\henk-\Development\RAUW\apps\mobile
npm i
```

## Running Locally

Open a separate terminal for each app you want to run.

### Backend API

```bash
cd C:\Users\henk-\Development\RAUW\apps\backend
php -S 127.0.0.1:8001 -t public
```

This serves the Laravel backend from the `public` directory at:

```text
http://127.0.0.1:8001
```

If you are working on Laravel Vite assets, run Vite in another backend terminal:

```bash
cd C:\Users\henk-\Development\RAUW\apps\backend
npm run dev
```

### Web App

```bash
cd C:\Users\henk-\Development\RAUW\apps\web
npm run dev
```

### Mobile App

```bash
cd C:\Users\henk-\Development\RAUW\apps\mobile
npm run start
```

Common mobile targets:

```bash
npm run android
npm run ios
npm run web
```

## When to Use `composer i` vs `npm i`

Use `composer i` in `apps/backend` when:

- Setting up the Laravel backend for the first time.
- `apps/backend/composer.json` or `apps/backend/composer.lock` changes.
- PHP dependencies are missing from `apps/backend/vendor`.

Use `npm i` in an app folder when:

- Setting up `apps/web`, `apps/mobile`, or backend Vite assets for the first time.
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
   - Mobile changes: `cd apps/mobile && npm i`
4. Start the backend if your work needs the API:

   ```bash
   cd C:\Users\henk-\Development\RAUW\apps\backend
   php -S 127.0.0.1:8001 -t public
   ```

5. Start the app you are actively developing from its own folder.
6. Keep each long-running process in its own terminal.

## Useful Commands

### Backend

```bash
cd C:\Users\henk-\Development\RAUW\apps\backend
php artisan migrate
php artisan test
npm run build
```

### Web

```bash
cd C:\Users\henk-\Development\RAUW\apps\web
npm run build
npm run lint
npm run preview
```

### Mobile

```bash
cd C:\Users\henk-\Development\RAUW\apps\mobile
npm run lint
```

## Backend Department Fixtures

When you run `php artisan migrate --seed` in `apps/backend`, local seeders create canonical rows in the backend `departments` table and assign demo actors to them.

- Managers have one or more departments through `department_ids` / the `department_manager` pivot.
- Officers have one or more departments through `department_ids` / the `department_officer` pivot.
- Use the Postman **Departments / List Departments** request to inspect local department IDs before creating managers or registering officers.
- Department deletion is blocked while a department is assigned to any manager or officer.
- Issue departments are derived from the selected category on create and whenever `category_id` changes on update. They are stored through the `department_issue` pivot and exposed to clients as a read-only `departments` array. Clients must not send a singular `department` field.
- **Planned (OpenAPI / Postman):** active main managers will replace officer or manager department pivots with `PATCH /api/officers/{officer}/departments` and `PATCH /api/managers/{manager}/departments`, body `{"department_ids":[1,2]}` or `[]` to clear. These routes are not in `routes/api.php` yet; use the Postman **Managers** folder requests after backend implementation.

## Backend District Fixtures

Local seeders also create canonical rows in the backend `districts` table and assign demo managers/officers through many-to-many pivots.

- Managers have zero or more districts through `district_ids` / the `district_manager` pivot.
- Officers have zero or more districts through `district_ids` / the `district_officer` pivot.
- Auth profile payloads for managers and officers return `districts` arrays of compact objects (`id`, `name`, `postal_prefix`), not a singular actor-side `district_id`.
- Use the Postman **Districts / List Districts** request to inspect local district IDs before creating managers, registering officers, or updating actor district assignments.
- Active managers and active officers can replace their own districts with `PATCH /api/auth/me/districts` and `{"district_ids":[1,2]}`. Active managers can replace an officer's districts with `PATCH /api/officers/{officer}/districts`. Regular users receive `403 Forbidden` from the self-service district endpoint because they do not have district assignments.
- Only active managers can create, update, or delete district records. District deletion is blocked while the district is assigned to any manager, assigned to any officer, or referenced by issues.
- Issue district handling is intentionally unchanged: `issues.district_id` remains a singular issue location/reference field and is out of scope for actor district many-to-many assignments.
