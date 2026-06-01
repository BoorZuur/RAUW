# Testing & Quality Gates

This repo contains three independent applications. Each one has its own test,
lint, and build commands, and is validated separately — there is no root-level
task runner or workspace orchestration.

All commands below are run from the respective app directory.

---

## Backend (`backend/`) — Laravel

The Laravel backend uses PHPUnit (configured via `phpunit.xml`) and is wrapped
by the `php artisan test` runner. Code style is enforced with Laravel Pint.

### Install

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

### Lint / format check

```bash
cd backend
vendor/bin/pint --test   # check-only (CI mode)
vendor/bin/pint          # apply fixes
```

### Run tests

```bash
cd backend
php artisan test
```

This is equivalent to the Composer script:

```bash
cd backend
composer test
```

### Migrate (required before tests against a real DB)

```bash
cd backend
php artisan migrate --force
```

### Build / validate

Laravel does not have a separate "build" step for the API. The CI pipeline
validates the backend by installing dependencies, running migrations, and
running `php artisan test`.

---

## Web (`web/`) — Vite + React

The web app is a Vite-powered React app. It ships with ESLint configured via
`eslint.config.js` and builds with `vite build`.

### Install

```bash
cd web
npm ci
cp .env.example .env
```

### Lint

```bash
cd web
npm run lint
```

### Build (production)

```bash
cd web
npm run build
```

Output is written to `web/dist/`.

### Preview the production build

```bash
cd web
npm run preview
```

### Tests

This app does not ship with a unit test runner by default. If/when tests are
added (e.g. Vitest or Playwright), add the corresponding script to
`web/package.json` and wire it into the `web` CI job.

The current web quality gate is: `npm ci` → `npm run lint` → `npm run build`.

---

## Mobile (`mobile/`) — Expo / React Native

The mobile app uses Expo (SDK 55) and TypeScript. It is linted via
`expo lint` and type-checked via the TypeScript compiler.

### Install

```bash
cd mobile
npm ci
cp .env.example .env
```

### Lint

```bash
cd mobile
npm run lint
```

### Type check

```bash
cd mobile
npx tsc --noEmit
```

### Validate the project against the installed Expo SDK

```bash
cd mobile
npx expo-doctor
```

### Tests

This app does not ship with a unit test runner by default. If/when tests are
added (e.g. `jest-expo`), add the corresponding script to `mobile/package.json`
and wire it into the `mobile` CI job.

The current mobile quality gate is: `npm ci` → `npm run lint` → `tsc --noEmit`.

### EAS-related validation

Production builds run on Expo Application Services (EAS) and are not part of
the standard PR CI run. See [DEPLOYMENT.md](./DEPLOYMENT.md) for EAS usage.

To check that the project is ready for an EAS build:

```bash
cd mobile
npx eas-cli@latest build:configure   # one-time, generates eas.json
npx expo-doctor
```

---

## Continuous Integration

CI lives in [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) and runs
three independent jobs on every push and pull request:

| Job       | Working dir | Steps                                                  |
| --------- | ----------- | ------------------------------------------------------ |
| `backend` | `backend/`  | composer install → pint --test → migrate → artisan test |
| `web`     | `web/`      | npm ci → npm run lint → npm run build                   |
| `mobile`  | `mobile/`   | npm ci → npm run lint → tsc --noEmit                    |

Jobs run in parallel and do not share caches or state, mirroring the
no-workspace layout of the repository.
