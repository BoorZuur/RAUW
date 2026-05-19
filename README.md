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

### Web (Vite + React)

The React web app lives in `web/` and is fully self-contained. All commands below are run from the `web/` directory.

**Requirements**

- Node.js 20.19+ (or 22.12+) — recommended LTS
- npm 10+ (bundled with Node, or use pnpm/yarn if preferred)

**First-time setup**

```bash
cd web
npm install
cp .env.example .env
```

**Run the development server**

```bash
cd web
npm run dev
```

Vite will print the local URL (default `http://localhost:5173`).

**Build for production**

```bash
cd web
npm run build
```

The build output is written to `web/dist/`.

**Preview the production build**

```bash
cd web
npm run preview
```

**Lint**

```bash
cd web
npm run lint
```

**Environment & secrets**

- `web/.env.example` is tracked and serves as the template.
- `web/.env` (and other local `.env.*` variants) are ignored and must never be committed.
- Only variables prefixed with `VITE_` are exposed to the client bundle (Vite convention). Never put server-only secrets in `web/.env`.
- The web app's environment is fully separate from `backend/.env` and `mobile/`.

### Mobile (Expo + React Native)

The Expo / React Native mobile app lives in `mobile/` and is fully self-contained. All commands below are run from the `mobile/` directory.

**Requirements**

- Node.js 20.19+ (or 22.12+) — recommended LTS
- npm 10+ (bundled with Node, or use pnpm/yarn if preferred)
- Expo CLI is invoked via `npx expo` — no global install required
- For native builds: Android Studio (Android) and/or Xcode on macOS (iOS)
- Optional: the Expo Go app on a physical device for quick previews

**First-time setup**

```bash
cd mobile
npm install
cp .env.example .env
```

**Run the development server**

```bash
cd mobile
npm run start
```

Expo will open the dev tools and print a QR code you can scan with Expo Go, plus shortcuts to launch a simulator/emulator.

**Run on a specific platform**

```bash
cd mobile
npm run android   # Android emulator or connected device
npm run ios       # iOS simulator (macOS only)
npm run web       # Run the app in a browser via react-native-web
```

**Lint**

```bash
cd mobile
npm run lint
```

**Production builds (EAS)**

Production builds use [EAS Build](https://docs.expo.dev/build/introduction/). EAS is not configured by default in this repo; to enable it:

```bash
cd mobile
npx eas-cli@latest login
npx eas-cli@latest build:configure
npx eas-cli@latest build --platform android
npx eas-cli@latest build --platform ios
```

To submit to the stores after a successful build:

```bash
cd mobile
npx eas-cli@latest submit --platform android
npx eas-cli@latest submit --platform ios
```

**Environment & secrets**

- `mobile/.env.example` is tracked and serves as the template.
- `.env*.local` files are ignored by `mobile/.gitignore` and must never be committed.
- Expo only exposes variables prefixed with `EXPO_PUBLIC_` to client code (accessible via `process.env.EXPO_PUBLIC_*`). Anything with that prefix is shipped to the device in plain text — **do not put secrets there**.
- Build-time and EAS secrets should be configured via [EAS environment variables and secrets](https://docs.expo.dev/build-reference/variables/), not via `mobile/.env`.
- The mobile app's environment is fully separate from `backend/.env` and `web/.env`.
