# Deployment Basics

Each application in this repo is deployed through its **own pipeline and to
its own target**. There is no unified release process — the backend, web app,
and mobile app are versioned and shipped independently.

This document describes the baseline expectations for each app. Concrete
provider configuration (Forge, Vercel, EAS, etc.) lives outside this repo or
in app-local configuration files.

---

## Backend (`backend/`) — Laravel

**Runtime:** PHP 8.3+ on any Laravel-compatible host (Laravel Forge,
Laravel Cloud, a VPS, a Docker image, AWS, etc.).

**Artifact:** the contents of the `backend/` directory.

### Release outline

1. Provision a server with PHP 8.3+, the required extensions, and a supported
   database.
2. Deploy `backend/` (e.g. via git pull on the server or a container build).
3. Install production dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. Configure environment variables (do **not** commit `.env`):

   ```bash
   cp .env.example .env
   php artisan key:generate
   # then fill in DB, mail, queue, third-party credentials
   ```

5. Run migrations:

   ```bash
   php artisan migrate --force
   ```

6. Cache configuration and routes for production:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. Point your web server (nginx/Apache) at `backend/public/` as the
   document root.

### Notes

- The backend has its own `.env` and secrets, fully independent from `web/`
  and `mobile/`.
- Queues, scheduling, and Laravel Horizon (if introduced later) belong to the
  backend deployment target only.
- Zero-downtime deploys, blue/green, etc. are provider-specific and out of
  scope for this document.

---

## Web (`web/`) — Vite + React

**Runtime:** any static host or CDN (Vercel, Netlify, Cloudflare Pages,
S3 + CloudFront, GitHub Pages, nginx serving static files, etc.).

**Artifact:** the contents of `web/dist/` produced by `npm run build`.

### Release outline

1. Install dependencies and build:

   ```bash
   cd web
   npm ci
   npm run build
   ```

2. Upload / publish `web/dist/` to the static host of your choice.
3. Configure environment variables on the host. Only `VITE_*` variables are
   exposed to the bundle and are baked in **at build time** — the build must
   be re-run for any env change to take effect.
4. Configure SPA routing fallback if you use client-side routing (typically
   rewriting unknown paths to `/index.html`).

### Notes

- The web app is fully decoupled from the backend at deploy time. It talks to
  the backend over HTTP using a `VITE_*` configured API base URL.
- Do not put server-side secrets in `web/.env` — anything `VITE_*` is shipped
  to every visitor.

---

## Mobile (`mobile/`) — Expo / React Native

**Runtime:** native iOS and Android binaries distributed through the App Store
and Google Play (or internal distribution channels).

**Artifact:** native builds produced by
[EAS Build](https://docs.expo.dev/build/introduction/).

### Release outline

1. One-time setup per machine / CI environment:

   ```bash
   cd mobile
   npx eas-cli@latest login
   npx eas-cli@latest build:configure
   ```

   This generates `mobile/eas.json` (commit it). Store and signing
   credentials are managed by EAS, not in this repo.

2. Build a release for each platform:

   ```bash
   cd mobile
   npx eas-cli@latest build --platform android
   npx eas-cli@latest build --platform ios
   ```

3. Submit to the stores:

   ```bash
   cd mobile
   npx eas-cli@latest submit --platform android
   npx eas-cli@latest submit --platform ios
   ```

4. Ship JS-only changes between native releases via
   [EAS Update](https://docs.expo.dev/eas-update/introduction/):

   ```bash
   cd mobile
   npx eas-cli@latest update --branch production
   ```

### Notes

- Mobile builds are **not** run in the GitHub Actions CI in this repo — they
  require EAS credentials and are typically triggered manually or from a
  dedicated release workflow.
- Build-time secrets must be configured as
  [EAS environment variables and secrets](https://docs.expo.dev/build-reference/variables/),
  **not** placed in `mobile/.env`.
- `EXPO_PUBLIC_*` variables are bundled into the client and visible to anyone
  with the app — never put secrets there.

---

## Summary

| App       | Pipeline target              | Artifact            | Release tool examples       |
| --------- | ---------------------------- | ------------------- | --------------------------- |
| Backend   | PHP/Laravel host             | `backend/` source   | Forge, Laravel Cloud, Docker |
| Web       | Static host / CDN            | `web/dist/`         | Vercel, Netlify, S3+CDN     |
| Mobile    | App Store / Play Store       | Native binaries     | EAS Build + EAS Submit      |

Each pipeline is owned independently. A change limited to one app should
never require a release of the other two.
