# RAW

**Real Art Walls** — a single repository containing three independent applications.

## Repository Structure

This repository is a plain single repo (no workspaces, no task runner, no shared packages). Each application lives in its own top-level directory and is fully self-contained with its own dependencies, tooling, and lifecycle.

```
RAW/
├── backend/   # Laravel (PHP) API — independent
├── web/       # Vite + React web app — independent
├── mobile/    # Expo / React Native mobile app — independent
└── plans/     # Implementation plans and documentation
```

### Apps

- **`backend/`** — Laravel PHP backend API. Managed with Composer.
- **`web/`** — React web application built with Vite. Managed with npm (or your preferred Node package manager).
- **`mobile/`** — React Native mobile app using Expo. Managed with npm (or your preferred Node package manager).

## Conventions

- **No shared code package.** The three apps do not share source code through a workspace or internal package. If something needs to be shared (e.g., API types), it is duplicated or generated per app.
- **Independent tooling.** Each app has its own `package.json` / `composer.json`, lockfile, scripts, and environment configuration.
- **Independent installs and runs.** Commands are always executed from within the respective app directory (`backend/`, `web/`, or `mobile/`).

## Getting Started

Each application is independent and installed/run from its own directory. Sections below cover per-app setup as each app is bootstrapped.

### Backend (Laravel)

The Laravel PHP backend lives in `backend/` and is fully self-contained. All commands below are run from the `backend/` directory.

**Requirements**

- PHP 8.2+ with the standard Laravel extensions
- Composer 2.x
- SQLite (default) — or configure another driver in `.env`

**First-time setup**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

> Note: Laravel's `create-project` workflow already generated a local `.env`, ran `key:generate`, created `database/database.sqlite`, and applied the initial migrations during bootstrap. The steps above are what a fresh clone of the repository needs.

**Run the development server**

```bash
cd backend
php artisan serve
```

The API will be available at `http://127.0.0.1:8000` by default.

**Run tests**

```bash
cd backend
php artisan test
```

**Environment & secrets**

- `backend/.env.example` is tracked and serves as the template.
- `backend/.env` is ignored by `backend/.gitignore` and must never be committed.
- Application keys, database credentials, and any third-party secrets belong in `backend/.env` only.

### Web & Mobile

The `web/` and `mobile/` apps will be bootstrapped in later phases; setup instructions will be added here once they exist.
